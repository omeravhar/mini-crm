<?php

namespace App\Support;

use App\Models\Integration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class IntegrationConnectionTester
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $usedStoredFields
     * @return array<string, mixed>
     */
    public function test(array $config, ?Integration $integration = null, array $usedStoredFields = []): array
    {
        return match ($config['platform'] ?? 'generic') {
            'meta' => $this->testMeta($config, $integration, $usedStoredFields),
            'google' => $this->testGoogle($config, $integration, $usedStoredFields),
            'tiktok' => $this->testTikTok($config, $integration, $usedStoredFields),
            default => $this->testGeneric($config, $integration, $usedStoredFields),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $usedStoredFields
     * @return array<string, mixed>
     */
    private function testMeta(array $config, ?Integration $integration, array $usedStoredFields): array
    {
        $checks = [];

        $this->appendStoredFieldUsageCheck($checks, $usedStoredFields);

        $status = (string) ($config['status'] ?? '');
        $verifyToken = trim((string) ($config['verify_token'] ?? ''));
        $accessToken = trim((string) ($config['access_token'] ?? ''));
        $pageId = trim((string) ($config['external_page_id'] ?? ''));
        $callbackUrl = $config['callback_url'] ?? null;
        $verifyUrl = $config['verify_url'] ?? null;
        $formIds = $this->normalizeFormIds($config['active_form_ids'] ?? []);
        $isPersisted = (bool) ($config['is_persisted'] ?? false);

        $this->check(
            $checks,
            'סטטוס',
            $status === 'active' ? 'success' : 'warning',
            $status === 'active'
                ? 'החיבור פעיל ולכן השרת יקבל webhook-ים מ-Meta.'
                : 'החיבור לא במצב פעיל. Meta יכולה לשלוח webhook, אבל השרת לא יעבד אותו עד שתעביר ל-Active.'
        );

        $this->check(
            $checks,
            'Verify Token',
            $verifyToken !== '' ? 'success' : 'error',
            $verifyToken !== ''
                ? 'יש Verify Token. זה הערך שמעתיקים גם למסך ה-Webhooks של Meta בזמן האימות הראשוני.'
                : 'חסר Verify Token. Meta לא תצליח לאמת את ה-webhook בלי ערך זהה גם כאן וגם בצד של Meta.'
        );

        $this->check(
            $checks,
            'Callback URL',
            $isPersisted ? 'success' : 'info',
            $isPersisted && is_string($callbackUrl) && $callbackUrl !== ''
                ? 'יש Callback URL אמיתי שאפשר להדביק ב-Meta.'
                : 'אחרי שמירת החיבור המערכת תיצור Callback URL אמיתי. לפני השמירה זו בדיקה חלקית בלבד.'
        );

        $this->check(
            $checks,
            'Verify URL',
            $isPersisted ? 'success' : 'info',
            $isPersisted && is_string($verifyUrl) && $verifyUrl !== ''
                ? 'יש Verify URL מוכן לאימות מול Meta.'
                : 'Verify URL אמיתי ייווצר אחרי שמירת החיבור.'
        );

        $this->check(
            $checks,
            'Access Token',
            $accessToken !== '' ? 'success' : 'error',
            $accessToken !== ''
                ? 'יש Access Token. המערכת יכולה לנסות למשוך פרטי ליד מלאים מ-Meta.'
                : 'חסר Access Token. בלי Access Token המערכת לא תוכל למשוך את פרטי הליד המלאים מ-Meta.'
        );

        $this->check(
            $checks,
            'Page / Asset ID',
            $pageId !== '' ? 'success' : 'warning',
            $pageId !== ''
                ? 'יש Page ID/Asset ID. זה מאפשר לבדוק גישה לעמוד ולטפסים שלו.'
                : 'מומלץ למלא Page / Asset ID כדי לוודא שה-token באמת רואה את העמוד שממנו מגיעים הלידים.'
        );

        if ($integration) {
            $this->check(
                $checks,
                'מיפויי טפסים פעילים',
                count($formIds) > 0 ? 'success' : 'warning',
                count($formIds) > 0
                    ? 'יש '.count($formIds).' מיפוי/י טפסים פעילים לחיבור הזה.'
                    : 'אין עדיין מיפוי טפסים פעיל. הלידים יכולים להגיע, אבל לא יהיה שיוך מסודר לפי Form ID.'
            );
        }

        if ($accessToken === '') {
            return $this->result('meta', $checks);
        }

        try {
            $api = Http::baseUrl((string) config('services.meta.graph_api_base', 'https://graph.facebook.com/v23.0'))
                ->acceptJson()
                ->timeout(15)
                ->connectTimeout(10);

            $meResponse = $api->get('me', [
                'fields' => 'id,name',
                'access_token' => $accessToken,
            ]);

            if ($meResponse->successful()) {
                $this->check(
                    $checks,
                    'תקינות Access Token',
                    'success',
                    'Meta אישרה את ה-token עבור '.($meResponse->json('name') ?: 'המשתמש המחובר').'.'
                );
            } else {
                $this->check(
                    $checks,
                    'תקינות Access Token',
                    'error',
                    $this->facebookErrorMessage($meResponse, 'Meta דחתה את ה-Access Token.')
                );

                return $this->result('meta', $checks);
            }

            if ($pageId === '') {
                return $this->result('meta', $checks);
            }

            $pageResponse = $api->get($pageId, [
                'fields' => 'id,name',
                'access_token' => $accessToken,
            ]);

            if ($pageResponse->successful()) {
                $this->check(
                    $checks,
                    'גישה לעמוד',
                    'success',
                    'ה-token הצליח לגשת לעמוד '.($pageResponse->json('name') ?: $pageId).'.'
                );
            } else {
                $this->check(
                    $checks,
                    'גישה לעמוד',
                    'error',
                    $this->facebookErrorMessage($pageResponse, 'ה-token לא הצליח לגשת לעמוד/asset שהוזן.')
                );

                return $this->result('meta', $checks);
            }

            $formsResponse = $api->get($pageId.'/leadgen_forms', [
                'fields' => 'id,name,status',
                'limit' => 100,
                'access_token' => $accessToken,
            ]);

            if (! $formsResponse->successful()) {
                $this->check(
                    $checks,
                    'גישה לטפסי לידים',
                    'error',
                    $this->facebookErrorMessage($formsResponse, 'Meta לא איפשרה למשוך את רשימת טפסי הלידים עבור העמוד הזה.')
                );

                return $this->result('meta', $checks);
            }

            $forms = collect($formsResponse->json('data') ?? [])
                ->filter(fn ($item) => is_array($item))
                ->values();

            $this->check(
                $checks,
                'גישה לטפסי לידים',
                'success',
                $forms->isNotEmpty()
                    ? 'Meta החזירה '.$forms->count().' טופס/ים עבור העמוד הזה.'
                    : 'העמוד נגיש, אבל Meta לא החזירה כרגע טפסי לידים.'
            );

            if ($formIds !== []) {
                $availableIds = $forms->pluck('id')
                    ->filter(fn ($id) => is_scalar($id) && (string) $id !== '')
                    ->map(fn ($id) => (string) $id)
                    ->all();

                $missingForms = array_values(array_diff($formIds, $availableIds));

                $this->check(
                    $checks,
                    'בדיקת Form ID',
                    $missingForms === [] ? 'success' : 'error',
                    $missingForms === []
                        ? 'כל ה-Form ID הפעילים שמוגדרים במערכת נמצאו גם ב-Meta.'
                        : 'Form ID חסרים או לא נגישים דרך ה-token: '.implode(', ', $missingForms)
                );
            }
        } catch (ConnectionException $exception) {
            $this->check(
                $checks,
                'תקשורת עם Meta',
                'error',
                'לא הצלחתי להתחבר ל-Meta מהשרת: '.$exception->getMessage()
            );
        }

        return $this->result('meta', $checks);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $usedStoredFields
     * @return array<string, mixed>
     */
    private function testTikTok(array $config, ?Integration $integration, array $usedStoredFields): array
    {
        $checks = [];

        $this->appendStoredFieldUsageCheck($checks, $usedStoredFields);

        $status = (string) ($config['status'] ?? '');
        $accessToken = trim((string) ($config['access_token'] ?? ''));
        $assetId = trim((string) ($config['external_page_id'] ?? ''));
        $isPersisted = (bool) ($config['is_persisted'] ?? false);

        $this->check(
            $checks,
            'סטטוס',
            $status === 'active' ? 'success' : 'warning',
            $status === 'active'
                ? 'החיבור פעיל ולכן השרת יקבל webhook-ים מ-TikTok.'
                : 'החיבור לא במצב פעיל. עדכן ל-Active לפני מעבר ל-production.'
        );

        $this->check(
            $checks,
            'Callback URL',
            $isPersisted ? 'success' : 'info',
            $isPersisted
                ? 'יש Callback URL מוכן להדבקה במסך ה-webhooks של TikTok.'
                : 'שמור את החיבור כדי לקבל Callback URL אמיתי.'
        );

        $this->check(
            $checks,
            'Access Token',
            $accessToken !== '' ? 'success' : 'warning',
            $accessToken !== ''
                ? 'יש Access Token שמור.'
                : 'אין Access Token. אם TikTok אצלך דורשת הרשאות API, מומלץ למלא אותו כבר עכשיו.'
        );

        $this->check(
            $checks,
            'Asset ID',
            $assetId !== '' ? 'success' : 'warning',
            $assetId !== ''
                ? 'יש Asset ID/Advertiser ID.'
                : 'מומלץ למלא Asset ID או Advertiser ID כדי לזהות לאיזה נכס החיבור שייך.'
        );

        if ($integration) {
            $activeMappings = $this->normalizeFormIds($config['active_form_ids'] ?? []);
            $this->check(
                $checks,
                'מיפויי טפסים פעילים',
                count($activeMappings) > 0 ? 'success' : 'warning',
                count($activeMappings) > 0
                    ? 'יש '.count($activeMappings).' מיפוי/י טפסים פעילים.'
                    : 'אין עדיין מיפוי טפסים פעיל.'
            );
        }

        $this->check(
            $checks,
            'בדיקה מול TikTok',
            'info',
            'כרגע המערכת מבצעת ל-TikTok בדיקת שלמות שדות בלבד. בדיקת API מרחוק נוסיף כשנחבר זרימת fetch ייעודית גם לפלטפורמה הזו.'
        );

        return $this->result('tiktok', $checks);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $usedStoredFields
     * @return array<string, mixed>
     */
    private function testGoogle(array $config, ?Integration $integration, array $usedStoredFields): array
    {
        $checks = [];

        $this->appendStoredFieldUsageCheck($checks, $usedStoredFields);

        $status = (string) ($config['status'] ?? '');
        $webhookKey = trim((string) ($config['webhook_secret'] ?? ''));
        $callbackUrl = $config['callback_url'] ?? null;
        $isPersisted = (bool) ($config['is_persisted'] ?? false);

        $this->check(
            $checks,
            'סטטוס',
            $status === 'active' ? 'success' : 'warning',
            $status === 'active'
                ? 'החיבור פעיל והשרת מוכן לקבל לידים מ-Google Ads.'
                : 'החיבור לא במצב פעיל. עדכן ל-Active לפני בדיקות מול Google Ads או מעבר ל-production.'
        );

        $this->check(
            $checks,
            'Webhook URL',
            $isPersisted ? 'success' : 'info',
            $isPersisted && is_string($callbackUrl) && $callbackUrl !== ''
                ? 'יש Callback URL מוכן שאפשר להדביק ב-Google Ads תחת Webhook URL.'
                : 'אחרי שמירת החיבור המערכת תיצור Callback URL אמיתי שאפשר להזין ב-Google Ads.'
        );

        $this->check(
            $checks,
            'Webhook Key',
            $webhookKey !== '' ? 'success' : 'error',
            $webhookKey !== ''
                ? 'יש Webhook Key שמור. השרת ישווה אותו לשדה google_key ש-Google שולחת בכל ליד.'
                : 'חסר Webhook Key. לפי Google Ads צריך להזין גם Webhook URL וגם Webhook Key כדי לאמת את המשלוחים.'
        );

        if ($integration) {
            $activeMappings = $this->normalizeFormIds($config['active_form_ids'] ?? []);
            $this->check(
                $checks,
                'מיפויי טפסים פעילים',
                count($activeMappings) > 0 ? 'success' : 'info',
                count($activeMappings) > 0
                    ? 'יש '.count($activeMappings).' מיפוי/י טפסים פעילים לחיבור הזה.'
                    : 'אין עדיין מיפוי טפסים פעיל. זה לא חוסם קבלת webhook, אבל מומלץ כדי לשייך owner ומיפוי שדות לפי form_id.'
            );
        }

        $this->check(
            $checks,
            'בדיקה מול Google Ads',
            'info',
            'כרגע הבדיקה מאמתת את שלמות ההגדרות המקומיות עבור Google Ads Lead Forms. אחרי השמירה הדבק את ה-Callback URL במסך ה-Webhook ואת ה-Webhook Secret בשדה Webhook Key ב-Google Ads.'
        );

        return $this->result('google', $checks);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $usedStoredFields
     * @return array<string, mixed>
     */
    private function testGeneric(array $config, ?Integration $integration, array $usedStoredFields): array
    {
        $checks = [];

        $this->appendStoredFieldUsageCheck($checks, $usedStoredFields);

        $status = (string) ($config['status'] ?? '');
        $isPersisted = (bool) ($config['is_persisted'] ?? false);
        $callbackUrl = $config['callback_url'] ?? null;

        $this->check(
            $checks,
            'סטטוס',
            $status === 'active' ? 'success' : 'warning',
            $status === 'active'
                ? 'החיבור פעיל ומוכן לקבל webhook-ים.'
                : 'החיבור לא במצב פעיל. עדכן ל-Active כדי שהשרת יעבד את האירועים.'
        );

        $this->check(
            $checks,
            'Callback URL',
            $isPersisted ? 'success' : 'info',
            $isPersisted && is_string($callbackUrl) && $callbackUrl !== ''
                ? 'יש Callback URL מוכן לשליחה למערכת החיצונית.'
                : 'אחרי שמירת החיבור ייווצר Callback URL אמיתי.'
        );

        if ($integration) {
            $activeMappings = $this->normalizeFormIds($config['active_form_ids'] ?? []);
            $this->check(
                $checks,
                'מיפויי טפסים פעילים',
                count($activeMappings) > 0 ? 'success' : 'info',
                count($activeMappings) > 0
                    ? 'יש '.count($activeMappings).' מיפוי/י טפסים פעילים.'
                    : 'אין עדיין מיפוי טפסים פעיל. זה לא חובה, אבל יעזור לסווג טפסים שונים.'
            );
        }

        $this->check(
            $checks,
            'בדיקה מול פלטפורמה',
            'info',
            'ב-Webhook כללי אין API אחיד לבדיקה מרחוק, לכן הבדיקה כאן מאמתת את שלמות ההגדרות המקומיות.'
        );

        return $this->result('generic', $checks);
    }

    /**
     * @param  array<int, array<string, string>>  $checks
     * @param  array<int, string>  $usedStoredFields
     */
    private function appendStoredFieldUsageCheck(array &$checks, array $usedStoredFields): void
    {
        if ($usedStoredFields === []) {
            return;
        }

        $this->check(
            $checks,
            'שימוש בערכים שמורים',
            'info',
            'השדות הבאים נבדקו מתוך הערכים שכבר שמורים במערכת כי הם הושארו ריקים בטופס: '.implode(', ', $usedStoredFields)
        );
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
     * @return array<string, mixed>
     */
    private function result(string $platform, array $checks): array
    {
        $errors = collect($checks)->where('status', 'error')->count();
        $warnings = collect($checks)->where('status', 'warning')->count();

        $summary = match (true) {
            $errors > 0 => 'נמצאו חסרים או שגיאות שמונעים בדיקה מלאה או קליטת לידים תקינה.',
            $warnings > 0 => 'החיבור כמעט מוכן, אבל יש עוד כמה נקודות שכדאי להשלים לפני עבודה מלאה.',
            default => 'הבדיקות עברו בהצלחה והחיבור נראה תקין.',
        };

        return [
            'platform' => $platform,
            'status' => $errors > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'success'),
            'ok' => $errors === 0,
            'summary' => $summary,
            'checked_at' => now()->format('Y-m-d H:i'),
            'checks' => $checks,
        ];
    }

    /**
     * @param  mixed  $formIds
     * @return array<int, string>
     */
    private function normalizeFormIds(mixed $formIds): array
    {
        if (! is_iterable($formIds)) {
            return [];
        }

        $normalized = [];

        foreach ($formIds as $formId) {
            if (! is_scalar($formId) || trim((string) $formId) === '') {
                continue;
            }

            $normalized[] = trim((string) $formId);
        }

        return array_values(array_unique($normalized));
    }

    private function facebookErrorMessage(Response $response, string $fallback): string
    {
        return (string) data_get($response->json(), 'error.message', $fallback.' (HTTP '.$response->status().').');
    }
}
