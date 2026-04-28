<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookEvent;
use App\Models\Integration;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IntegrationWebhookController extends Controller
{
    public function metaVerify(Request $request, Integration $integration): Response
    {
        abort_unless(
            $integration->platform === 'meta'
            && in_array($integration->status, ['draft', 'active'], true),
            404
        );

        $mode = $request->query('hub_mode');
        $verifyToken = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        abort_unless(
            $mode === 'subscribe'
            && $integration->verify_token
            && hash_equals($integration->verify_token, (string) $verifyToken),
            403
        );

        return response((string) $challenge, 200);
    }

    public function metaReceive(Request $request, Integration $integration): JsonResponse
    {
        abort_unless($integration->platform === 'meta' && $integration->status === 'active', 404);

        return $this->receive($request, $integration, 'meta');
    }

    public function googleReceive(Request $request, Integration $integration): JsonResponse
    {
        abort_unless($integration->platform === 'google' && $integration->status === 'active', 404);

        if (! $this->googleKeyMatches($integration, $request)) {
            return response()->json([
                'message' => 'Invalid google_key.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->receive($request, $integration, 'google', Response::HTTP_OK, (object) []);
    }

    public function tiktokReceive(Request $request, Integration $integration): JsonResponse
    {
        abort_unless($integration->platform === 'tiktok' && $integration->status === 'active', 404);

        return $this->receive($request, $integration, 'tiktok');
    }

    public function genericReceive(Request $request, Integration $integration): JsonResponse
    {
        abort_unless($integration->platform === 'generic' && $integration->status === 'active', 404);

        return $this->receive($request, $integration, 'generic', Response::HTTP_OK, [
            'ok' => true,
            'status' => 'success',
            'message' => 'Lead received successfully.',
        ]);
    }

    private function receive(
        Request $request,
        Integration $integration,
        string $platform,
        int $successStatus = Response::HTTP_ACCEPTED,
        mixed $responsePayload = null,
    ): JsonResponse
    {
        $payload = $this->payloadFromRequest($request);

        $event = WebhookEvent::create([
            'integration_id' => $integration->id,
            'platform' => $platform,
            'event_type' => $this->extractEventType($platform, $payload),
            'external_event_id' => $this->extractExternalEventId($payload),
            'external_form_id' => $this->extractExternalFormId($payload),
            'status' => 'received',
            'headers' => collect($request->headers->all())
                ->map(fn (array $values) => implode(', ', $values))
                ->all(),
            'payload' => $payload,
            'received_at' => now(),
        ]);

        $integration->forceFill([
            'last_webhook_at' => now(),
            'last_error_at' => null,
            'last_error_message' => null,
        ])->save();

        // Process immediately so webhooks work without a background queue worker.
        (new ProcessWebhookEvent($event->id))->handle();

        if (is_array($responsePayload)) {
            $responsePayload = array_merge([
                'event_id' => $event->id,
            ], $responsePayload);
        }

        return response()->json($responsePayload ?? [
            'status' => 'accepted',
            'event_id' => $event->id,
        ], $successStatus);
    }

    private function extractEventType(string $platform, array $payload): string
    {
        return match ($platform) {
            'meta' => (string) (data_get($payload, 'entry.0.changes.0.field') ?: data_get($payload, 'object') ?: 'meta_event'),
            'google' => (string) (data_get($payload, 'lead_stage') ?: (data_get($payload, 'is_test') ? 'google_test_lead' : 'google_lead')),
            'tiktok' => (string) (data_get($payload, 'event') ?: data_get($payload, 'type') ?: 'tiktok_event'),
            default => (string) ($payload['event_type'] ?? 'generic_event'),
        };
    }

    private function extractExternalEventId(array $payload): ?string
    {
        foreach ([
            data_get($payload, 'event_id'),
            data_get($payload, 'id'),
            data_get($payload, 'lead_id'),
            data_get($payload, 'data.event_id'),
            data_get($payload, 'data.lead_id'),
            data_get($payload, 'entry.0.id'),
        ] as $candidate) {
            if (is_scalar($candidate) && (string) $candidate !== '') {
                return (string) $candidate;
            }
        }

        return null;
    }

    private function extractExternalFormId(array $payload): ?string
    {
        foreach ([
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

    private function payloadFromRequest(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            $payload = $request->all();
        }

        if (! is_array($payload)) {
            $payload = ['_raw' => $request->getContent()];
        }

        return $payload;
    }

    private function googleKeyMatches(Integration $integration, Request $request): bool
    {
        if (! filled($integration->webhook_secret)) {
            return true;
        }

        $providedKey = $this->extractGoogleKey($this->payloadFromRequest($request));

        return is_string($providedKey)
            && $providedKey !== ''
            && hash_equals($integration->webhook_secret, $providedKey);
    }

    private function extractGoogleKey(array $payload): ?string
    {
        foreach ([
            data_get($payload, 'google_key'),
            data_get($payload, 'Google_key'),
            data_get($payload, 'data.google_key'),
        ] as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return null;
    }
}
