<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>כניסה ל-{{ config('app.name', 'EeasyCRM') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.rtl.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            text-align: right;
        }
        .login-card {
            width: min(420px, 92vw);
            border: 0;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
        }
        .login-brand {
            display: inline-flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 0.5rem;
            line-height: 1.1;
        }
        .login-brand__slogan {
            color: #6c757d;
            direction: ltr;
            font-size: 0.78rem;
            font-weight: 500;
            unicode-bidi: isolate;
        }
    </style>
    @include('partials.global-css')
</head>
@php
    $appName = config('app.name', 'EeasyCRM');
    $appSlogan = config('app.slogan', 'Exactly What You Need');
@endphp
<body class="d-flex align-items-center justify-content-center">
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="mb-4">
                <div class="login-brand text-muted small fw-semibold">
                    <span>{{ $appName }}</span>
                    <span class="login-brand__slogan" dir="ltr">{{ $appSlogan }}</span>
                </div>
                <h1 class="h3 mb-1">התחברות</h1>
                <p class="text-muted mb-0">הזן את פרטי הגישה שלך כדי להמשיך.</p>
            </div>

            @include('partials.alerts')

            <form method="POST" action="{{ route('login.submit') }}" class="d-grid gap-3">
                @csrf
                <div>
                    <label class="form-label" for="email">דוא"ל</label>
                    <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div>
                    <label class="form-label" for="password">סיסמה</label>
                    <input class="form-control" id="password" name="password" type="password" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                    <label class="form-check-label" for="remember">זכור אותי</label>
                </div>
                <button class="btn btn-primary" type="submit">כניסה</button>
            </form>
        </div>
    </div>
</body>
</html>
