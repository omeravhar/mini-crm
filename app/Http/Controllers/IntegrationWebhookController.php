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
        abort_unless($integration->platform === 'meta' && $integration->status === 'active', 404);

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

    public function tiktokReceive(Request $request, Integration $integration): JsonResponse
    {
        abort_unless($integration->platform === 'tiktok' && $integration->status === 'active', 404);

        return $this->receive($request, $integration, 'tiktok');
    }

    public function genericReceive(Request $request, Integration $integration): JsonResponse
    {
        abort_unless($integration->platform === 'generic' && $integration->status === 'active', 404);

        return $this->receive($request, $integration, 'generic');
    }

    private function receive(Request $request, Integration $integration, string $platform): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            $payload = $request->all();
        }

        if (! is_array($payload)) {
            $payload = ['_raw' => $request->getContent()];
        }

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

        ProcessWebhookEvent::dispatch($event->id);

        return response()->json([
            'status' => 'accepted',
            'event_id' => $event->id,
        ], Response::HTTP_ACCEPTED);
    }

    private function extractEventType(string $platform, array $payload): string
    {
        return match ($platform) {
            'meta' => (string) (data_get($payload, 'entry.0.changes.0.field') ?: data_get($payload, 'object') ?: 'meta_event'),
            'tiktok' => (string) (data_get($payload, 'event') ?: data_get($payload, 'type') ?: 'tiktok_event'),
            default => (string) ($payload['event_type'] ?? 'generic_event'),
        };
    }

    private function extractExternalEventId(array $payload): ?string
    {
        foreach ([
            data_get($payload, 'event_id'),
            data_get($payload, 'id'),
            data_get($payload, 'data.event_id'),
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
}
