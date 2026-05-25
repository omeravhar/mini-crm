<?php

namespace App\Jobs;

use App\Models\IntegrationFormMapping;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Support\LeadAssignmentNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $webhookEventId,
    ) {
    }

    public function handle(): void
    {
        $event = WebhookEvent::with(['integration.formMappings', 'lead'])->find($this->webhookEventId);

        if (! $event) {
            return;
        }

        $event->forceFill([
            'status' => 'processing',
            'error_message' => null,
        ])->save();

        try {
            $payload = is_array($event->payload) ? $event->payload : [];
            $normalizedLead = $this->extractNormalizedLeadPayload($payload);
            $externalFormId = $event->external_form_id ?: $this->extractExternalFormId($payload, $normalizedLead);
            $pendingFetchMessage = null;

            if (! $normalizedLead && $event->platform === 'meta') {
                $normalizedLead = $this->fetchMetaLeadPayload($event, $payload, $pendingFetchMessage);
                $externalFormId = $externalFormId ?: $this->extractExternalFormId($payload, $normalizedLead);
            }

            $mapping = $this->resolveFormMapping($event, $externalFormId);

            if (! $normalizedLead && $event->platform === 'generic' && $payload !== []) {
                $normalizedLead = $payload;
            }

            if (! $normalizedLead) {
                if ($pendingFetchMessage) {
                    $this->markPendingFetch($event, $pendingFetchMessage);

                    return;
                }

                $this->markPendingFetch($event, 'האירוע התקבל ונשמר, אך נדרש חיבור API מלא למשיכת פרטי ליד מהפלטפורמה.');

                return;
            }

            $normalizedLead = $this->applyFieldMap($normalizedLead, $mapping, $payload);

            $externalLeadId = $normalizedLead['external_lead_id']
                ?? $this->extractExternalLeadId($payload)
                ?? null;
            $email = $normalizedLead['email'] ?? null;
            $phone = $normalizedLead['phone'] ?? null;

            if (! $externalLeadId && ! $email && ! $phone && ! $this->hasLeadCreationData($normalizedLead)) {
                $this->markPendingFetch($event, 'האירוע נשמר, אך חסרים מזהים מספקים ליצירת ליד בתוך ה-CRM.');

                return;
            }

            [$firstName, $lastName] = $this->resolveNameParts($normalizedLead);
            $ownerId = $this->resolveOwnerId($normalizedLead, $mapping);
            $interestedIn = $this->resolveInterestedIn($normalizedLead);
            $campaignName = $this->resolveCampaignName($normalizedLead, $payload);
            $formNameAsCampaignFallback = $this->stringValue($mapping?->external_form_name);

            $lead = $this->findExistingLead($event->platform, $externalLeadId, $email, $phone) ?? new Lead();
            $previousOwnerId = $lead->owner_id;
            $leadType = $this->resolveLeadType($normalizedLead, $lead);

            $leadData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email ?: $lead->email,
                'phone' => $phone,
                'company' => $normalizedLead['company'] ?? $lead->company,
                'job_title' => $normalizedLead['job_title'] ?? $lead->job_title,
                'website' => $normalizedLead['website'] ?? $lead->website,
                'source' => in_array(($normalizedLead['source'] ?? null), ['website', 'inbound_call', 'outbound', 'referral', 'event', 'social', 'partner'], true)
                    ? $normalizedLead['source']
                    : 'social',
                'status' => in_array(($normalizedLead['status'] ?? null), LeadStatus::values(), true)
                    ? $normalizedLead['status']
                    : ($lead->status ?: 'new'),
                'priority' => in_array(($normalizedLead['priority'] ?? null), ['low', 'medium', 'high'], true)
                    ? $normalizedLead['priority']
                    : ($lead->priority ?: 'medium'),
                'expected_value' => $normalizedLead['expected_value'] ?? $lead->expected_value,
                'interested_in' => $interestedIn ?? $lead->interested_in,
                'lead_type' => $leadType,
                'notes' => $normalizedLead['notes'] ?? $lead->notes,
                'owner_id' => $ownerId,
                'created_by' => $lead->exists ? $lead->created_by : $ownerId,
                'pipeline' => in_array(($normalizedLead['pipeline'] ?? null), ['default', 'enterprise', 'smb'], true)
                    ? $normalizedLead['pipeline']
                    : ($lead->pipeline ?: 'default'),
                'stage' => in_array(($normalizedLead['stage'] ?? null), ['lead', 'mql', 'sql', 'negotiation', 'won'], true)
                    ? $normalizedLead['stage']
                    : ($lead->stage ?: 'lead'),
                'visibility' => in_array(($normalizedLead['visibility'] ?? null), ['team', 'private'], true)
                    ? $normalizedLead['visibility']
                    : ($lead->visibility ?: 'team'),
                'source_platform' => $event->platform,
                'source_channel' => $normalizedLead['source_channel'] ?? $event->platform,
                'external_lead_id' => $externalLeadId,
                'external_form_id' => $externalFormId,
                'external_campaign_id' => $normalizedLead['external_campaign_id'] ?? $this->extractExternalCampaignId($payload),
                'external_campaign_name' => $campaignName ?? $lead->external_campaign_name ?? $formNameAsCampaignFallback,
                'external_ad_id' => $normalizedLead['external_ad_id'] ?? $this->extractExternalAdId($payload),
                'raw_payload' => $payload,
                'received_at' => $lead->received_at ?? $event->received_at ?? now(),
                'closed_at' => in_array(($normalizedLead['status'] ?? $lead->status), LeadStatus::closedValues(), true)
                    ? ($lead->closed_at ?: now())
                    : null,
            ];

            $lead->fill($leadData);
            $lead->save();

            app(LeadAssignmentNotifier::class)->notifyIfAssigned($lead, $previousOwnerId);

            $event->forceFill([
                'lead_id' => $lead->id,
                'external_form_id' => $externalFormId,
                'status' => 'processed',
                'error_message' => null,
                'processed_at' => now(),
            ])->save();

            $this->logProcessedWebhook($event, $lead, $payload, $normalizedLead);

            $event->integration?->forceFill([
                'last_error_at' => null,
                'last_error_message' => null,
            ])->save();
        } catch (Throwable $exception) {
            $event->forceFill([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ])->save();

            $event->integration?->forceFill([
                'last_error_at' => now(),
                'last_error_message' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    private function markPendingFetch(WebhookEvent $event, string $message): void
    {
        $event->forceFill([
            'status' => 'pending_fetch',
            'error_message' => $message,
            'processed_at' => now(),
        ])->save();

        Log::warning('Webhook event marked as pending_fetch.', [
            'webhook_event_id' => $event->id,
            'integration_id' => $event->integration_id,
            'platform' => $event->platform,
            'event_type' => $event->event_type,
            'external_form_id' => $event->external_form_id,
            'message' => $message,
            'payload' => is_array($event->payload) ? $event->payload : [],
            'headers' => is_array($event->headers) ? $event->headers : [],
        ]);
    }

    private function resolveFormMapping(WebhookEvent $event, ?string &$externalFormId): ?IntegrationFormMapping
    {
        $activeMappings = $event->integration?->formMappings
            ?->filter(fn (IntegrationFormMapping $item) => $item->is_active)
            ->values();

        if (! $activeMappings || $activeMappings->isEmpty()) {
            return null;
        }

        if (filled($externalFormId)) {
            return $activeMappings->first(
                fn (IntegrationFormMapping $item) => (string) $item->external_form_id === (string) $externalFormId
            );
        }

        if ($activeMappings->count() === 1) {
            $mapping = $activeMappings->first();
            $externalFormId = $mapping?->external_form_id ?: $externalFormId;

            return $mapping;
        }

        return null;
    }

    private function logProcessedWebhook(WebhookEvent $event, Lead $lead, array $payload, array $normalizedLead): void
    {
        Log::info('Webhook event processed successfully.', [
            'webhook_event_id' => $event->id,
            'integration_id' => $event->integration_id,
            'lead_id' => $lead->id,
            'platform' => $event->platform,
            'event_type' => $event->event_type,
            'external_form_id' => $event->external_form_id,
            'payload' => $payload,
            'normalized_lead' => $normalizedLead,
            'headers' => is_array($event->headers) ? $event->headers : [],
        ]);
    }

    private function extractNormalizedLeadPayload(array $payload): ?array
    {
        foreach ([
            data_get($payload, 'lead'),
            data_get($payload, 'data.lead'),
            data_get($payload, 'lead_data'),
            data_get($payload, 'data.lead_data'),
        ] as $candidate) {
            if (is_array($candidate)) {
                return $candidate;
            }
        }

        $googleLead = $this->extractGoogleLeadPayload($payload);

        if ($googleLead) {
            return $googleLead;
        }

        if ($this->looksLikeFlatLeadPayload($payload)) {
            return $payload;
        }

        return null;
    }

    private function looksLikeFlatLeadPayload(array $payload): bool
    {
        foreach ([
            'full_name',
            'name',
            'first_name',
            'last_name',
            'email',
            'phone',
            'company',
            'company_name',
            'job_title',
            'website',
            'notes',
            'external_lead_id',
        ] as $field) {
            $value = data_get($payload, $field);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function extractGoogleLeadPayload(array $payload): ?array
    {
        $userColumnData = data_get($payload, 'user_column_data', data_get($payload, 'data.user_column_data'));

        if (! is_iterable($userColumnData)) {
            return null;
        }

        $googleFields = [];

        foreach ($userColumnData as $item) {
            if (! is_array($item)) {
                continue;
            }

            $columnId = strtoupper(trim((string) ($item['column_id'] ?? '')));
            $value = $item['string_value'] ?? null;

            if ($columnId === '' || ! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $googleFields[$columnId] = trim((string) $value);
        }

        if ($googleFields === []) {
            return null;
        }

        $normalizedLead = [
            'external_lead_id' => $this->extractExternalLeadId($payload),
            'external_form_id' => $this->extractExternalFormId($payload),
            'external_campaign_id' => $this->extractExternalCampaignId($payload),
            'external_campaign_name' => $this->stringValue(
                data_get($payload, 'campaign_name', data_get($payload, 'campaign.name', data_get($payload, 'data.campaign_name')))
            ),
            'external_ad_id' => $this->extractExternalAdId($payload),
            'source' => 'partner',
            'source_channel' => 'google_ads',
            'google_fields' => $googleFields,
        ];

        foreach ([
            'full_name' => ['FULL_NAME'],
            'first_name' => ['FIRST_NAME'],
            'last_name' => ['LAST_NAME'],
            'email' => ['EMAIL', 'WORK_EMAIL'],
            'phone' => ['PHONE_NUMBER', 'WORK_PHONE'],
            'company' => ['COMPANY_NAME'],
            'job_title' => ['JOB_TITLE'],
            'street' => ['STREET_ADDRESS'],
            'zip' => ['POSTAL_CODE'],
            'city' => ['CITY'],
            'country' => ['COUNTRY'],
            'interested_in' => ['PRODUCT', 'SERVICE', 'OFFER', 'CATEGORY'],
        ] as $field => $columnIds) {
            foreach ($columnIds as $columnId) {
                if (array_key_exists($columnId, $googleFields)) {
                    $normalizedLead[$field] = $googleFields[$columnId];
                    break;
                }
            }
        }

        return array_filter($normalizedLead, fn ($value) => ! is_null($value) && $value !== '' && $value !== []);
    }

    private function fetchMetaLeadPayload(WebhookEvent $event, array &$payload, ?string &$pendingFetchMessage): ?array
    {
        $leadId = $this->extractExternalLeadId($payload);

        if (! $leadId) {
            $pendingFetchMessage = 'Meta webhook was saved, but it did not include a leadgen_id to fetch.';

            return null;
        }

        $accessToken = $event->integration?->access_token;

        if (! filled($accessToken)) {
            $pendingFetchMessage = 'Meta webhook was saved, but the integration has no Access Token for fetching lead details.';

            return null;
        }

        try {
            $response = Http::baseUrl((string) config('services.meta.graph_api_base', 'https://graph.facebook.com/v23.0'))
                ->acceptJson()
                ->timeout(15)
                ->connectTimeout(10)
                ->get($leadId, [
                    'fields' => implode(',', [
                        'id',
                        'created_time',
                        'ad_id',
                        'ad_name',
                        'adset_id',
                        'adset_name',
                        'campaign_id',
                        'campaign_name',
                        'form_id',
                        'field_data',
                    ]),
                    'access_token' => $accessToken,
                ]);
        } catch (ConnectionException $exception) {
            $pendingFetchMessage = 'Meta webhook was saved, but the server could not connect to Meta: '.$exception->getMessage();

            return null;
        }

        if (! $response->successful()) {
            $pendingFetchMessage = (string) data_get(
                $response->json(),
                'error.message',
                'Meta webhook was saved, but Meta rejected the lead details request. HTTP '.$response->status().'.'
            );

            return null;
        }

        $fetchedPayload = $response->json();

        if (! is_array($fetchedPayload)) {
            $pendingFetchMessage = 'Meta webhook was saved, but Meta returned an invalid lead details payload.';

            return null;
        }

        $payload['_meta_fetched_lead'] = $fetchedPayload;
        $event->forceFill(['payload' => $payload])->save();

        return $this->normalizeMetaLeadResponse($fetchedPayload, $payload);
    }

    private function normalizeMetaLeadResponse(array $metaLead, array $webhookPayload): array
    {
        $normalizedLead = [
            'external_lead_id' => $this->stringValue($metaLead['id'] ?? null) ?: $this->extractExternalLeadId($webhookPayload),
            'external_form_id' => $this->stringValue($metaLead['form_id'] ?? null) ?: $this->extractExternalFormId($webhookPayload),
            'external_campaign_id' => $this->stringValue($metaLead['campaign_id'] ?? null) ?: $this->extractExternalCampaignId($webhookPayload),
            'external_campaign_name' => $this->stringValue($metaLead['campaign_name'] ?? null),
            'external_ad_id' => $this->stringValue($metaLead['ad_id'] ?? null) ?: $this->extractExternalAdId($webhookPayload),
            'source' => 'social',
            'source_channel' => 'facebook',
            'meta_fields' => [],
        ];

        $fieldData = $metaLead['field_data'] ?? [];

        if (is_iterable($fieldData)) {
            foreach ($fieldData as $field) {
                if (! is_array($field)) {
                    continue;
                }

                $name = trim((string) ($field['name'] ?? ''));
                $value = $this->metaFieldValue($field['values'] ?? null);

                if ($name === '' || $value === null || $value === '') {
                    continue;
                }

                $normalizedLead['meta_fields'][$name] = $value;

                $crmField = $this->metaFieldNameToCrmField($name);

                if ($crmField && ! filled($normalizedLead[$crmField] ?? null)) {
                    $normalizedLead[$crmField] = $value;
                }
            }
        }

        return array_filter($normalizedLead, fn ($value) => ! is_null($value) && $value !== '' && $value !== []);
    }

    private function metaFieldValue(mixed $values): ?string
    {
        if (is_array($values)) {
            $items = array_values(array_filter(array_map(
                fn ($value) => is_scalar($value) ? trim((string) $value) : null,
                $values
            ), fn ($value) => filled($value)));

            return $items === [] ? null : implode(', ', $items);
        }

        return is_scalar($values) && trim((string) $values) !== ''
            ? trim((string) $values)
            : null;
    }

    private function metaFieldNameToCrmField(string $name): ?string
    {
        $key = (string) Str::of($name)
            ->lower()
            ->replace(['"', "'", '״', '׳', '`'], '')
            ->replace([' ', '-', '.', '/', '\\'], '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_');

        return match ($key) {
            'full_name', 'fullname', 'name', 'שם', 'שם_מלא', 'שם_לקוח', 'שם_הלקוח' => 'full_name',
            'first_name', 'firstname', 'שם_פרטי' => 'first_name',
            'last_name', 'lastname', 'שם_משפחה' => 'last_name',
            'email', 'work_email', 'דואל', 'אימייל', 'מייל', 'כתובת_דואל', 'כתובת_אימייל', 'כתובת_מייל' => 'email',
            'phone', 'phone_number', 'mobile_phone', 'work_phone', 'טלפון', 'מספר_טלפון', 'נייד', 'טלפון_נייד', 'מספר_נייד', 'פלאפון' => 'phone',
            'company', 'company_name', 'חברה', 'שם_חברה', 'שם_החברה' => 'company',
            'job_title', 'תפקיד' => 'job_title',
            'website', 'אתר', 'אתר_אינטרנט' => 'website',
            'street', 'street_address' => 'street',
            'zip', 'zip_code', 'postal_code' => 'zip',
            'city', 'עיר' => 'city',
            'country', 'מדינה' => 'country',
            default => null,
        };
    }

    private function stringValue(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== ''
            ? trim((string) $value)
            : null;
    }

    private function applyFieldMap(array $normalizedLead, ?IntegrationFormMapping $mapping, array $payload): array
    {
        if (! $mapping || empty($mapping->field_map)) {
            return $normalizedLead;
        }

        foreach ($mapping->field_map as $crmField => $sourcePath) {
            if (! is_string($crmField) || ! is_string($sourcePath) || $sourcePath === '') {
                continue;
            }

            $mappedValue = data_get($normalizedLead, $sourcePath, data_get($payload, $sourcePath));

            if (! is_null($mappedValue) && $mappedValue !== '') {
                $normalizedLead[$crmField] = $mappedValue;
            }
        }

        return $normalizedLead;
    }

    private function resolveNameParts(array $normalizedLead): array
    {
        $firstName = trim((string) ($normalizedLead['first_name'] ?? ''));
        $lastName = trim((string) ($normalizedLead['last_name'] ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            return [$firstName !== '' ? $firstName : 'ליד', $lastName];
        }

        $fullName = trim((string) ($normalizedLead['full_name'] ?? $normalizedLead['name'] ?? ''));

        if ($fullName === '') {
            return ['ליד', 'נכנס'];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [$fullName];
        $first = array_shift($parts);

        return [$first ?: 'ליד', trim(implode(' ', $parts))];
    }

    private function resolveInterestedIn(array $normalizedLead): ?string
    {
        foreach ([
            $normalizedLead['interested_in'] ?? null,
            $normalizedLead['interest'] ?? null,
            $normalizedLead['interested_product'] ?? null,
            $normalizedLead['interested_service'] ?? null,
        ] as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return null;
    }

    private function resolveCampaignName(array $normalizedLead, array $payload): ?string
    {
        $campaignId = $this->stringValue(
            $normalizedLead['external_campaign_id'] ?? $this->extractExternalCampaignId($payload)
        );

        foreach ([
            $normalizedLead['external_campaign_name'] ?? null,
            $normalizedLead['campaign_name'] ?? null,
            $normalizedLead['campaign'] ?? null,
            data_get($payload, 'external_campaign_name'),
            data_get($payload, 'campaign_name'),
            data_get($payload, 'campaign.name'),
            data_get($payload, 'data.campaign_name'),
            data_get($payload, 'data.campaign.name'),
            data_get($payload, 'entry.0.changes.0.value.campaign_name'),
        ] as $candidate) {
            $campaignName = $this->normalizeCampaignNameCandidate($candidate, $campaignId);

            if ($campaignName !== null) {
                return $campaignName;
            }
        }

        return null;
    }

    private function normalizeCampaignNameCandidate(mixed $candidate, ?string $campaignId): ?string
    {
        $campaignName = $this->stringValue($candidate);

        if ($campaignName === null) {
            return null;
        }

        return $campaignId !== null && $campaignName === $campaignId
            ? null
            : $campaignName;
    }

    private function resolveLeadType(array $normalizedLead, Lead $lead): string
    {
        $candidate = strtolower(trim((string) ($normalizedLead['lead_type'] ?? $normalizedLead['contact_type'] ?? '')));

        if (in_array($candidate, ['new', 'returning'], true)) {
            return $candidate;
        }

        return $lead->exists ? 'returning' : 'new';
    }

    private function resolveOwnerId(array $normalizedLead, ?IntegrationFormMapping $mapping): ?int
    {
        $candidate = $normalizedLead['owner_id'] ?? null;

        if (is_numeric($candidate) && User::whereKey((int) $candidate)->exists()) {
            return (int) $candidate;
        }

        return $mapping?->default_owner_id;
    }

    private function hasLeadCreationData(array $normalizedLead): bool
    {
        foreach ([
            $normalizedLead['full_name'] ?? null,
            $normalizedLead['name'] ?? null,
            $normalizedLead['first_name'] ?? null,
            $normalizedLead['last_name'] ?? null,
            $normalizedLead['company'] ?? null,
            $normalizedLead['company_name'] ?? null,
            $normalizedLead['notes'] ?? null,
            $normalizedLead['interested_in'] ?? null,
        ] as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return true;
            }
        }

        return false;
    }

    private function findExistingLead(string $platform, ?string $externalLeadId, ?string $email, ?string $phone): ?Lead
    {
        if ($externalLeadId) {
            $lead = Lead::where('source_platform', $platform)
                ->where('external_lead_id', $externalLeadId)
                ->first();

            if ($lead) {
                return $lead;
            }
        }

        if ($email) {
            $lead = Lead::where('email', $email)->first();

            if ($lead) {
                return $lead;
            }
        }

        if ($phone) {
            return Lead::where('phone', $phone)->first();
        }

        return null;
    }

    private function extractExternalLeadId(array $payload): ?string
    {
        foreach ([
            data_get($payload, 'external_lead_id'),
            data_get($payload, 'lead_id'),
            data_get($payload, 'data.lead_id'),
            data_get($payload, 'entry.0.changes.0.value.leadgen_id'),
        ] as $candidate) {
            if (is_scalar($candidate) && (string) $candidate !== '') {
                return (string) $candidate;
            }
        }

        return null;
    }

    private function extractExternalFormId(array $payload, ?array $normalizedLead = null): ?string
    {
        foreach ([
            $normalizedLead['external_form_id'] ?? null,
            data_get($payload, 'external_form_id'),
            data_get($payload, 'form_id'),
            data_get($payload, 'data.form_id'),
            data_get($payload, 'entry.0.changes.0.value.form_id'),
        ] as $candidate) {
            if (is_scalar($candidate) && (string) $candidate !== '') {
                return (string) $candidate;
            }
        }

        return null;
    }

    private function extractExternalCampaignId(array $payload): ?string
    {
        foreach ([
            data_get($payload, 'external_campaign_id'),
            data_get($payload, 'campaign_id'),
            data_get($payload, 'campaign.id'),
            data_get($payload, 'data.campaign_id'),
            data_get($payload, 'data.campaign.id'),
            data_get($payload, 'entry.0.changes.0.value.campaign_id'),
        ] as $candidate) {
            if (is_scalar($candidate) && (string) $candidate !== '') {
                return (string) $candidate;
            }
        }

        return null;
    }

    private function extractExternalAdId(array $payload): ?string
    {
        foreach ([
            data_get($payload, 'external_ad_id'),
            data_get($payload, 'ad_id'),
            data_get($payload, 'creative_id'),
            data_get($payload, 'data.ad_id'),
            data_get($payload, 'data.creative_id'),
        ] as $candidate) {
            if (is_scalar($candidate) && (string) $candidate !== '') {
                return (string) $candidate;
            }
        }

        return null;
    }
}
