<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\IntegrationFormMapping;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IntegrationController extends Controller
{
    public function index()
    {
        $this->requireAdmin();

        return view('integrations.index', [
            'integrations' => Integration::with(['formMappings.defaultOwner'])
                ->withCount([
                    'webhookEvents',
                    'webhookEvents as failed_webhook_events_count' => fn ($query) => $query->where('status', 'failed'),
                ])
                ->latest()
                ->get(),
            'users' => User::orderBy('name')->get(),
            'webhookEvents' => WebhookEvent::with(['integration', 'lead'])
                ->latest('received_at')
                ->take(50)
                ->get(),
            'platformOptions' => $this->platformOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireAdmin();
        $data = $this->validatedIntegrationData($request);
        $data['webhook_key'] = (string) Str::uuid();

        Integration::create($data);

        return back()->with('success', 'האינטגרציה נוצרה בהצלחה.');
    }

    public function update(Request $request, Integration $integration): RedirectResponse
    {
        $this->requireAdmin();
        $data = $this->validatedIntegrationData($request, $integration);

        foreach (['access_token', 'refresh_token', 'verify_token', 'webhook_secret'] as $sensitiveField) {
            if (! array_key_exists($sensitiveField, $data)) {
                continue;
            }

            if ($data[$sensitiveField] === '' || is_null($data[$sensitiveField])) {
                unset($data[$sensitiveField]);
            }
        }

        $integration->update($data);

        return back()->with('success', 'האינטגרציה עודכנה בהצלחה.');
    }

    public function destroy(Integration $integration): RedirectResponse
    {
        $this->requireAdmin();
        $integration->delete();

        return back()->with('success', 'האינטגרציה נמחקה בהצלחה.');
    }

    public function storeMapping(Request $request, Integration $integration): RedirectResponse
    {
        $this->requireAdmin();
        $data = $this->validatedMappingData($request, $integration);
        $integration->formMappings()->create($data);

        return back()->with('success', 'מיפוי הטופס נוצר בהצלחה.');
    }

    public function updateMapping(Request $request, IntegrationFormMapping $mapping): RedirectResponse
    {
        $this->requireAdmin();
        $data = $this->validatedMappingData($request, $mapping->integration, $mapping);
        $mapping->update($data);

        return back()->with('success', 'מיפוי הטופס עודכן בהצלחה.');
    }

    public function destroyMapping(IntegrationFormMapping $mapping): RedirectResponse
    {
        $this->requireAdmin();
        $mapping->delete();

        return back()->with('success', 'מיפוי הטופס נמחק בהצלחה.');
    }

    private function validatedIntegrationData(Request $request, ?Integration $integration = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'platform' => ['required', Rule::in(array_keys($this->platformOptions()))],
            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
            'external_account_id' => ['nullable', 'string', 'max:150'],
            'external_page_id' => ['nullable', 'string', 'max:150'],
            'access_token' => ['nullable', 'string'],
            'refresh_token' => ['nullable', 'string'],
            'verify_token' => ['nullable', 'string', 'max:150'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'config_json' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['config'] = $this->parseJsonTextarea($data['config_json'] ?? null, 'config_json');
        unset($data['config_json']);

        if (! $integration) {
            foreach (['access_token', 'refresh_token', 'verify_token', 'webhook_secret'] as $field) {
                $data[$field] = $data[$field] ?? null;
            }
        }

        return $data;
    }

    private function validatedMappingData(Request $request, Integration $integration, ?IntegrationFormMapping $mapping = null): array
    {
        $data = $request->validate([
            'external_form_id' => [
                'required',
                'string',
                'max:150',
                Rule::unique('integration_form_mappings', 'external_form_id')
                    ->where(fn ($query) => $query->where('integration_id', $integration->id))
                    ->ignore($mapping?->id),
            ],
            'external_form_name' => ['nullable', 'string', 'max:150'],
            'default_owner_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
            'field_map_json' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['field_map'] = $this->parseJsonTextarea($data['field_map_json'] ?? null, 'field_map_json');
        unset($data['field_map_json']);

        return $data;
    }

    private function parseJsonTextarea(?string $json, string $field): ?array
    {
        $json = trim((string) $json);

        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => 'יש להזין JSON תקין במבנה אובייקט או מערך.',
            ]);
        }

        return $decoded;
    }

    private function platformOptions(): array
    {
        return [
            'meta' => 'Meta / Facebook / Instagram',
            'tiktok' => 'TikTok',
            'generic' => 'Webhook חיצוני כללי',
        ];
    }

    private function statusOptions(): array
    {
        return [
            'draft' => 'טיוטה',
            'active' => 'פעיל',
            'disabled' => 'מושבת',
        ];
    }
}
