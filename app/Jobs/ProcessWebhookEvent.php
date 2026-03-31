<?php

namespace App\Jobs;

use App\Models\IntegrationFormMapping;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

            if (! $normalizedLead) {
                $this->markPendingFetch($event, 'האירוע התקבל ונשמר, אך נדרש חיבור API מלא למשיכת פרטי ליד מהפלטפורמה.');

                return;
            }

            $mapping = $event->integration?->formMappings
                ?->first(fn (IntegrationFormMapping $item) => $item->is_active && $item->external_form_id === $externalFormId);

            $normalizedLead = $this->applyFieldMap($normalizedLead, $mapping, $payload);

            $externalLeadId = $normalizedLead['external_lead_id']
                ?? $this->extractExternalLeadId($payload)
                ?? null;
            $email = $normalizedLead['email'] ?? null;
            $phone = $normalizedLead['phone'] ?? null;

            if (! $externalLeadId && ! $email && ! $phone) {
                $this->markPendingFetch($event, 'האירוע נשמר, אך חסרים מזהים מספקים ליצירת ליד בתוך ה-CRM.');

                return;
            }

            [$firstName, $lastName] = $this->resolveNameParts($normalizedLead);
            $ownerId = $this->resolveOwnerId($normalizedLead, $mapping);

            $lead = $this->findExistingLead($event->platform, $externalLeadId, $email, $phone) ?? new Lead();

            $leadData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email ?: ($lead->email ?: 'lead-' . now()->timestamp . '@placeholder.local'),
                'phone' => $phone,
                'company' => $normalizedLead['company'] ?? $lead->company,
                'job_title' => $normalizedLead['job_title'] ?? $lead->job_title,
                'website' => $normalizedLead['website'] ?? $lead->website,
                'source' => in_array(($normalizedLead['source'] ?? null), ['website', 'inbound_call', 'outbound', 'referral', 'event', 'social', 'partner'], true)
                    ? $normalizedLead['source']
                    : 'social',
                'status' => in_array(($normalizedLead['status'] ?? null), ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'], true)
                    ? $normalizedLead['status']
                    : ($lead->status ?: 'new'),
                'priority' => in_array(($normalizedLead['priority'] ?? null), ['low', 'medium', 'high'], true)
                    ? $normalizedLead['priority']
                    : ($lead->priority ?: 'medium'),
                'expected_value' => $normalizedLead['expected_value'] ?? $lead->expected_value,
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
                'external_ad_id' => $normalizedLead['external_ad_id'] ?? $this->extractExternalAdId($payload),
                'raw_payload' => $payload,
                'received_at' => now(),
                'closed_at' => in_array(($normalizedLead['status'] ?? $lead->status), ['won', 'lost'], true)
                    ? ($lead->closed_at ?: now())
                    : null,
            ];

            $lead->fill($leadData);
            $lead->save();

            $event->forceFill([
                'lead_id' => $lead->id,
                'external_form_id' => $externalFormId,
                'status' => 'processed',
                'error_message' => null,
                'processed_at' => now(),
            ])->save();

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

        return null;
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

    private function resolveOwnerId(array $normalizedLead, ?IntegrationFormMapping $mapping): ?int
    {
        $candidate = $normalizedLead['owner_id'] ?? null;

        if (is_numeric($candidate) && User::whereKey((int) $candidate)->exists()) {
            return (int) $candidate;
        }

        return $mapping?->default_owner_id;
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
            data_get($payload, 'data.campaign_id'),
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
            data_get($payload, 'data.ad_id'),
        ] as $candidate) {
            if (is_scalar($candidate) && (string) $candidate !== '') {
                return (string) $candidate;
            }
        }

        return null;
    }
}
