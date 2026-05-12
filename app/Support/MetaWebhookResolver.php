<?php

namespace App\Support;

use App\Models\Integration;
use Illuminate\Database\Eloquent\Builder;

class MetaWebhookResolver
{
    public function receiveUrl(): string
    {
        return route('webhooks.meta.shared.receive');
    }

    public function verifyUrl(): string
    {
        return route('webhooks.meta.shared.verify');
    }

    public function verifyToken(?Integration $integration = null): ?string
    {
        $sharedToken = trim((string) config('services.meta.webhook_verify_token', ''));

        if ($sharedToken !== '') {
            return $sharedToken;
        }

        $integrationToken = trim((string) ($integration?->verify_token ?? ''));

        return $integrationToken !== '' ? $integrationToken : null;
    }

    public function findIntegrationByVerifyToken(?string $verifyToken): ?Integration
    {
        $verifyToken = trim((string) $verifyToken);

        if ($verifyToken === '') {
            return null;
        }

        return $this->metaIntegrations(['draft', 'active'])
            ->where('verify_token', $verifyToken)
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->first();
    }

    public function resolveIntegration(array $payload): ?Integration
    {
        $formId = $this->extractFormId($payload);
        $pageIds = $this->extractPageIds($payload);

        if ($formId !== null) {
            $formCandidates = $this->metaIntegrations()
                ->whereHas('formMappings', function (Builder $query) use ($formId) {
                    $query->where('is_active', true)
                        ->where('external_form_id', $formId);
                })
                ->get();

            if ($pageIds !== []) {
                $pageMatchedIntegration = $formCandidates->first(function (Integration $integration) use ($pageIds) {
                    return in_array((string) $integration->external_page_id, $pageIds, true);
                });

                if ($pageMatchedIntegration) {
                    return $pageMatchedIntegration;
                }
            }

            if ($formCandidates->count() === 1) {
                return $formCandidates->first();
            }
        }

        if ($pageIds !== []) {
            $pageCandidates = $this->metaIntegrations()
                ->whereIn('external_page_id', $pageIds)
                ->get();

            if ($pageCandidates->count() === 1) {
                return $pageCandidates->first();
            }
        }

        $activeIntegrations = $this->metaIntegrations()->get();

        return $activeIntegrations->count() === 1
            ? $activeIntegrations->first()
            : null;
    }

    /**
     * @return array<int, string>
     */
    public function extractPageIds(array $payload): array
    {
        $pageIds = [];

        foreach ([
            data_get($payload, 'page_id'),
            data_get($payload, 'data.page_id'),
            data_get($payload, 'entry.0.id'),
            data_get($payload, 'entry.0.changes.0.value.page_id'),
        ] as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                $pageIds[] = trim((string) $candidate);
            }
        }

        foreach ((array) data_get($payload, 'entry', []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ([
                $entry['id'] ?? null,
                data_get($entry, 'changes.0.value.page_id'),
            ] as $candidate) {
                if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                    $pageIds[] = trim((string) $candidate);
                }
            }
        }

        return array_values(array_unique($pageIds));
    }

    public function extractFormId(array $payload): ?string
    {
        foreach ([
            data_get($payload, 'external_form_id'),
            data_get($payload, 'form_id'),
            data_get($payload, 'data.form_id'),
            data_get($payload, 'entry.0.changes.0.value.form_id'),
        ] as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function metaIntegrations(array $statuses = ['active']): Builder
    {
        return Integration::query()
            ->where('platform', 'meta')
            ->whereIn('status', $statuses);
    }
}
