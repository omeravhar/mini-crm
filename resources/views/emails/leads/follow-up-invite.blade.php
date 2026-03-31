<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>פגישת מעקב לליד</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f5f7fb; padding: 24px; color: #1f2937;">
    <div style="max-width: 680px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h1 style="margin-top: 0; font-size: 24px;">פגישת מעקב לליד נקבעה עבורך</h1>

        <p>שלום {{ $recipient->name }},</p>

        <p>
            נקבעה פגישת מעקב לליד <strong>{{ $lead->full_name }}</strong>
            בתאריך <strong>{{ $scheduledAt->format('d/m/Y') }}</strong>
            בשעה <strong>{{ $scheduledAt->format('H:i') }}</strong>.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
            <tbody>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; width: 160px;"><strong>שם ליד</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">{{ $lead->full_name }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;"><strong>חברה</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">{{ $lead->company ?: 'ללא חברה' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;"><strong>דוא"ל</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">{{ $lead->email }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;"><strong>טלפון</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">{{ $lead->phone ?: 'לא הוזן' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>הערות</strong></td>
                    <td style="padding: 10px;">{{ $lead->notes ?: 'ללא הערות' }}</td>
                </tr>
            </tbody>
        </table>

        <p style="margin-bottom: 24px;">
            ניתן לפתוח את הליד ישירות מהמערכת דרך הקישור הבא:
        </p>

        <p style="margin-bottom: 0;">
            <a href="{{ $leadUrl }}" style="display: inline-block; background: #0d6efd; color: #ffffff; text-decoration: none; padding: 12px 18px; border-radius: 10px;">
                פתיחת הליד במערכת
            </a>
        </p>
    </div>
</body>
</html>
