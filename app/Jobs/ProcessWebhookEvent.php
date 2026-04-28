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
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
            $mapping = $this->resolveFormMapping($event, $externalFormId);

            if (! $normalizedLead && $event->platform === 'generic' && $payload !== []) {
                $normalizedLead = $payload;
            }

            if (! $normalizedLead) {
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
                'external_campaign_name' => $campaignName ?? $lead->external_campaign_name,
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
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return null;
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
