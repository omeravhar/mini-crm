<?php

namespace App\Support;

use App\Models\Integration;
use App\Models\IntegrationFormMapping;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MetaIntegrationSynchronizer
{
    /**
     * @return array<string, mixed>
     */
    public function sync(Integration $integration): array
    {
        $integration->loadMissing('formMappings');

        $checks = [];
        $forms = [];

        if ($integration->platform !== 'meta') {
            $this->check(
                $checks,
                'פלטפורמה',
                'error',
                'סנכרון Meta זמין רק לחיבורים מסוג Meta / Facebook / Instagram.'
            );

            return $this->result($checks, $forms);
        }

        $accessToken = trim((string) $integration->access_token);
        $pageId = trim((string) $integration->external_page_id);
        $resolver = app(MetaWebhookResolver::class);
        $callbackUrl = $resolver->receiveUrl();
        $verifyUrl = $resolver->verifyUrl();
        $verifyToken = $resolver->verifyToken($integration);

        $this->check(
            $checks,
            'Callback URL',
            'success',
            'כתובת הקליטה מוכנה: '.$callbackUrl
        );

        $this->check(
            $checks,
            'Verify URL',
            filled($verifyToken) ? 'success' : 'warning',
            filled($verifyToken)
                ? 'יש Verify Token שמוכן לאימות מול Meta: '.$verifyUrl
                : 'חסר Verify Token. שמור Verify Token משותף ב-META_WEBHOOK_VERIFY_TOKEN או הזן Verify Token בחיבור.'
        );

        $this->syncAppWebhookSubscription($integration, $callbackUrl, $checks);

        if ($accessToken === '') {
            $this->check(
                $checks,
                'Access Token',
                'error',
                'חסר Access Token שמור. קודם שמור token תקף בחיבור ואז הרץ סנכרון Meta.'
            );

            return $this->result($checks, $forms);
        }

        try {
            $meResponse = $this->graph()->get('me', [
                'fields' => 'id,name',
                'access_token' => $accessToken,
            ]);

            if (! $meResponse->successful()) {
                $this->check(
                    $checks,
                    'Access Token',
                    'error',
                    $this->facebookErrorMessage($meResponse, 'Meta דחתה את ה-Access Token.')
                );

                return $this->result($checks, $forms);
            }

            $this->check(
                $checks,
                'Access Token',
                'success',
                'Meta אישרה את ה-token עבור '.($meResponse->json('name') ?: 'המשתמש המחובר').'.'
            );

            $pages = $this->fetchAvailablePages($accessToken, $checks);
            $pageAccessToken = $this->pageAccessTokenFromPages($pages, $pageId);

            if ($pageId === '') {
                if (count($pages) === 1) {
                    $page = $pages[0];
                    $pageId = (string) $page['id'];
                    $pageAccessToken = $this->stringValue($page['access_token'] ?? null);
                    $this->storePageDetails($integration, $pageId, $this->stringValue($page['name'] ?? null));

                    $this->check(
                        $checks,
                        'Page ID',
                        'success',
                        'זוהה דף יחיד מתוך ה-token ונשמר Page ID: '.($page['name'] ?? $pageId).'.'
                    );
                } else {
                    $this->check(
                        $checks,
                        'Page ID',
                        'error',
                        $pages === []
                            ? 'לא הוזן Page ID ולא הצלחנו לשלוף רשימת דפים מה-token.'
                            : 'לא הוזן Page ID ול-token יש גישה לכמה דפים: '.$this->pageNames($pages).'. הזן את הדף הרצוי והריץ סנכרון שוב.'
                    );

                    return $this->result($checks, $forms);
                }
            }

            $pageResponse = $this->graph()->get($pageId, [
                'fields' => 'id,name',
                'access_token' => $accessToken,
            ]);

            if (! $pageResponse->successful()) {
                $this->check(
                    $checks,
                    'גישה לדף',
                    'error',
                    $this->facebookErrorMessage($pageResponse, 'ה-token לא הצליח לגשת לדף שהוזן.')
                );

                return $this->result($checks, $forms);
            }

            $pageName = $pageResponse->json('name') ?: $pageId;
            $this->storePageDetails($integration, $pageId, $this->stringValue($pageName));

            $this->check(
                $checks,
                'גישה לדף',
                'success',
                'ה-token ניגש בהצלחה לדף '.$pageName.'.'
            );

            $operationToken = $pageAccessToken ?: $accessToken;

            $this->syncPageSubscription($pageId, $operationToken, $checks);

            $formsResponse = $this->graph()->get($pageId.'/leadgen_forms', [
                'fields' => 'id,name,status',
                'limit' => 100,
                'access_token' => $operationToken,
            ]);

            if (! $formsResponse->successful()) {
                $this->check(
                    $checks,
                    'טפסי לידים',
                    'error',
                    $this->facebookErrorMessage($formsResponse, 'Meta לא איפשרה למשוך את רשימת טפסי הלידים.')
                );

                return $this->result($checks, $forms);
            }

            $forms = $this->normalizeForms($formsResponse->json('data') ?? []);

            $this->check(
                $checks,
                'טפסי לידים',
                $forms === [] ? 'warning' : 'success',
                $forms === []
                    ? 'הדף נגיש, אבל Meta לא החזירה טפסי לידים עבורו.'
                    : 'נמצאו '.$this->formatCount(count($forms), 'טופס', 'טפסים').' ב-Meta: '.$this->formNames($forms).'.'
            );

            if ($forms !== []) {
                $this->syncFormMappings($integration->fresh(['formMappings']) ?: $integration, $forms, $checks);
            }
        } catch (ConnectionException $exception) {
            $this->check(
                $checks,
                'תקשורת עם Meta',
                'error',
                'לא הצלחנו להתחבר ל-Meta מהשרת: '.$exception->getMessage()
            );
        }

        return $this->result($checks, $forms);
    }

    private function graph(): PendingRequest
    {
        return Http::baseUrl((string) config('services.meta.graph_api_base', 'https://graph.facebook.com/v23.0'))
            ->acceptJson()
            ->timeout(20)
            ->connectTimeout(10);
    }

    /**
     * @param  array<int, array<string, string>>  $checks
     */
    private function syncAppWebhookSubscription(Integration $integration, string $callbackUrl, array &$checks): void
    {
        $appId = trim((string) config('services.meta.app_id', ''));
        $appSecret = trim((string) config('services.meta.app_secret', ''));
        $verifyToken = trim((string) (app(MetaWebhookResolver::class)->verifyToken($integration) ?? ''));

        if ($appId === '' || $appSecret === '') {
            $this->check(
                $checks,
                'Meta App Webhook',
                'warning',
                'לא הוגדרו META_APP_ID ו-META_APP_SECRET בשרת. לכן הסנכרון יכול לחבר את הדף ל-leadgen, אבל לא יכול לוודא או לרשום את Callback URL ברמת האפליקציה.'
            );

            return;
        }

        if ($verifyToken === '') {
            $this->check(
                $checks,
                'Meta App Webhook',
                'warning',
                'חסר Verify Token. בלי META_WEBHOOK_VERIFY_TOKEN או Verify Token בחיבור אי אפשר לרשום אוטומטית Webhook ברמת האפליקציה.'
            );

            return;
        }

        $appToken = $appId.'|'.$appSecret;

        $subscriptionsResponse = $this->graph()->get($appId.'/subscriptions', [
            'access_token' => $appToken,
        ]);

        if ($subscriptionsResponse->successful()) {
            $pageSubscription = collect($subscriptionsResponse->json('data') ?? [])
                ->filter(fn ($item) => is_array($item) && ($item['object'] ?? null) === 'page')
                ->first();

            $fields = collect(data_get($pageSubscription, 'fields', []))
                ->map(fn ($field) => is_array($field) ? ($field['name'] ?? null) : $field)
                ->filter()
                ->map(fn ($field) => (string) $field)
                ->values()
                ->all();

            $existingCallback = is_scalar(data_get($pageSubscription, 'callback_url'))
                ? (string) data_get($pageSubscription, 'callback_url')
                : null;

            if ($existingCallback === $callbackUrl && in_array('leadgen', $fields, true)) {
                $this->check(
                    $checks,
                    'Meta App Webhook',
                    'success',
                    'האפליקציה כבר רשומה ל-Page leadgen עם Callback URL של החיבור הזה.'
                );

                return;
            }

            if ($existingCallback && $existingCallback !== $callbackUrl) {
                $this->check(
                    $checks,
                    'Meta App Webhook קיים',
                    'warning',
                    'באפליקציה מוגדר Callback URL אחר: '.$existingCallback.'. ננסה לעדכן אותו לכתובת של החיבור הנוכחי.'
                );
            }
        } else {
            $this->check(
                $checks,
                'Meta App Webhook',
                'warning',
                $this->facebookErrorMessage($subscriptionsResponse, 'לא הצלחנו לקרוא את ה-Webhooks שמוגדרים באפליקציית Meta. ננסה לרשום אותם מחדש.')
            );
        }

        $subscribeResponse = $this->graph()->asForm()->post($appId.'/subscriptions', [
            'object' => 'page',
            'fields' => 'leadgen',
            'callback_url' => $callbackUrl,
            'verify_token' => $verifyToken,
            'access_token' => $appToken,
        ]);

        $this->check(
            $checks,
            'רישום Meta App Webhook',
            $subscribeResponse->successful() ? 'success' : 'error',
            $subscribeResponse->successful()
                ? 'Meta אישרה Webhook ברמת האפליקציה עבור Page leadgen ל-Callback URL הנוכחי.'
                : $this->facebookErrorMessage($subscribeResponse, 'Meta לא אישרה את רישום ה-Webhook ברמת האפליקציה.')
        );
    }

    /**
     * @param  array<int, array<string, string>>  $checks
     * @return array<int, array<string, mixed>>
     */
    private function fetchAvailablePages(string $accessToken, array &$checks): array
    {
        $response = $this->graph()->get('me/accounts', [
            'fields' => 'id,name,access_token,tasks',
            'limit' => 100,
            'access_token' => $accessToken,
        ]);

        if (! $response->successful()) {
            $this->check(
                $checks,
                'רשימת דפים',
                'warning',
                $this->facebookErrorMessage($response, 'לא הצלחנו לשלוף את רשימת הדפים מה-token. אם הוזן Page ID נמשיך לבדוק אותו ישירות.')
            );

            return [];
        }

        $pages = collect($response->json('data') ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['id'] ?? null))
            ->values()
            ->all();

        $this->check(
            $checks,
            'רשימת דפים',
            $pages === [] ? 'warning' : 'success',
            $pages === []
                ? 'ה-token תקף, אבל לא החזיר דפים זמינים דרך me/accounts.'
                : 'ה-token רואה '.$this->formatCount(count($pages), 'דף', 'דפים').': '.$this->pageNames($pages).'.'
        );

        return $pages;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     */
    private function pageAccessTokenFromPages(array $pages, string $pageId): ?string
    {
        if ($pageId === '') {
            return null;
        }

        foreach ($pages as $page) {
            if ((string) ($page['id'] ?? '') === $pageId) {
                return $this->stringValue($page['access_token'] ?? null);
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, string>>  $checks
     */
    private function syncPageSubscription(string $pageId, string $accessToken, array &$checks): void
    {
        $subscriptionsResponse = $this->graph()->get($pageId.'/subscribed_apps', [
            'access_token' => $accessToken,
        ]);

        if ($subscriptionsResponse->successful()) {
            $subscriptions = collect($subscriptionsResponse->json('data') ?? [])
                ->filter(fn ($item) => is_array($item))
                ->values();

            $this->check(
                $checks,
                'בדיקת subscribed_apps',
                'success',
                $subscriptions->isEmpty()
                    ? 'לא נמצאו חיבורי אפליקציה קיימים לדף. ננסה לחבר את האפליקציה לשדה leadgen.'
                    : 'נמצאו '.$subscriptions->count().' חיבורי אפליקציה קיימים לדף. ננסה לוודא שגם האפליקציה הנוכחית מחוברת ל-leadgen.'
            );
        } else {
            $this->check(
                $checks,
                'בדיקת subscribed_apps',
                'warning',
                $this->facebookErrorMessage($subscriptionsResponse, 'לא הצלחנו לקרוא את רשימת האפליקציות שמחוברות לדף. ננסה לחבר בכל זאת.')
            );
        }

        $subscribeResponse = $this->graph()->asForm()->post($pageId.'/subscribed_apps', [
            'subscribed_fields' => 'leadgen',
            'access_token' => $accessToken,
        ]);

        $success = $subscribeResponse->successful()
            && ($subscribeResponse->json('success') === true || $subscribeResponse->json('success') === null);

        $this->check(
            $checks,
            'חיבור leadgen webhook',
            $success ? 'success' : 'error',
            $success
                ? 'Meta אישרה חיבור של האפליקציה לדף עבור leadgen. מעכשיו ליד אמיתי או ליד בדיקה אמור להגיע ל-Callback URL.'
                : $this->facebookErrorMessage($subscribeResponse, 'Meta לא אישרה את חיבור האפליקציה לדף עבור leadgen.')
        );
    }

    /**
     * @param  mixed  $items
     * @return array<int, array{id: string, name: string|null, status: string|null}>
     */
    private function normalizeForms(mixed $items): array
    {
        if (! is_iterable($items)) {
            return [];
        }

        $forms = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! filled($item['id'] ?? null)) {
                continue;
            }

            $forms[] = [
                'id' => (string) $item['id'],
                'name' => $this->stringValue($item['name'] ?? null),
                'status' => $this->stringValue($item['status'] ?? null),
            ];
        }

        return $forms;
    }

    /**
     * @param  array<int, array{id: string, name: string|null, status: string|null}>  $forms
     * @param  array<int, array<string, string>>  $checks
     */
    private function syncFormMappings(Integration $integration, array $forms, array &$checks): void
    {
        $created = 0;
        $updated = 0;
        $existingByFormId = $integration->formMappings->keyBy(fn (IntegrationFormMapping $mapping) => (string) $mapping->external_form_id);

        foreach ($forms as $form) {
            $mapping = $existingByFormId->get($form['id']);

            if (! $mapping) {
                $integration->formMappings()->create([
                    'external_form_id' => $form['id'],
                    'external_form_name' => $form['name'],
                    'is_active' => ! in_array(strtoupper((string) $form['status']), ['ARCHIVED', 'DELETED'], true),
                ]);

                $created++;

                continue;
            }

            if (! filled($mapping->external_form_name) && filled($form['name'])) {
                $mapping->update(['external_form_name' => $form['name']]);
                $updated++;
            }
        }

        $this->check(
            $checks,
            'סנכרון מיפויי טפסים',
            'success',
            'נוצרו '.$created.' מיפויים חדשים ועודכנו '.$updated.' מיפויים קיימים. מיפויים ידניים קיימים לא נדרסו.'
        );
    }

    private function storePageDetails(Integration $integration, string $pageId, ?string $pageName): void
    {
        $config = is_array($integration->config) ? $integration->config : [];

        if ($pageName) {
            $config['meta_page_name'] = $pageName;
        }

        $integration->forceFill([
            'external_page_id' => $pageId,
            'config' => $config === [] ? null : $config,
        ])->save();
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     */
    private function pageNames(array $pages): string
    {
        return collect($pages)
            ->map(fn ($page) => trim((string) ($page['name'] ?? $page['id'] ?? '')))
            ->filter()
            ->take(6)
            ->implode(', ');
    }

    /**
     * @param  array<int, array{id: string, name: string|null, status: string|null}>  $forms
     */
    private function formNames(array $forms): string
    {
        return collect($forms)
            ->map(fn ($form) => $form['name'] ?: $form['id'])
            ->take(8)
            ->implode(', ');
    }

    private function formatCount(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }

    private function stringValue(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== ''
            ? trim((string) $value)
            : null;
    }

    /**
     * @param  array<int, array<string, string>>  $checks
     */
    private function check(array &$checks, string $label, string $status, string $message): void
    {
        $checks[] = [
            'label' => $label,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $checks
     * @param  array<int, array{id: string, name: string|null, status: string|null}>  $forms
     * @return array<string, mixed>
     */
    private function result(array $checks, array $forms): array
    {
        $errors = collect($checks)->where('status', 'error')->count();
        $warnings = collect($checks)->where('status', 'warning')->count();

        $summary = match (true) {
            $errors > 0 => 'סנכרון Meta הסתיים עם שגיאות. החיבור עדיין לא מוכן לקבלת ליד אמיתי באופן מלא.',
            $warnings > 0 => 'סנכרון Meta הושלם חלקית. יש נקודות שצריך להשלים לפני מעבר מלא ל-production.',
            default => 'סנכרון Meta הושלם בהצלחה. אפשר לשלוח ליד בדיקה מ-Lead Ads Testing Tool או ליד אמיתי.',
        };

        return [
            'platform' => 'meta',
            'status' => $errors > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'success'),
            'ok' => $errors === 0,
            'summary' => $summary,
            'checked_at' => now()->format('Y-m-d H:i'),
            'forms' => $forms,
            'checks' => $checks,
        ];
    }

    private function facebookErrorMessage(Response $response, string $fallback): string
    {
        return (string) data_get($response->json(), 'error.message', $fallback.' (HTTP '.$response->status().').');
    }
}
