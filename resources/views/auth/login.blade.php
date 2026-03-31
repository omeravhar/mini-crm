<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>כניסה למערכת CRM</title>
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
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="mb-4">
                <div class="text-muted small fw-semibold">מערכת CRM</div>
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
