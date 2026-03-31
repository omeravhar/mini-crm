<?php

namespace App\Http\Controllers;

use App\Models\AuthActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthSessionController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            $this->logAuthActivity(
                $request,
                AuthActivityLog::LOGIN_FAILED,
                null,
                $request->input('email'),
                'validation_error',
                AuthActivityLog::STATUS_FAILED
            );

            return back()->withErrors($validator)->onlyInput('email');
        }

        $credentials = $validator->validated();
        $matchedUser = User::where('email', $credentials['email'])->first();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->logAuthActivity(
                $request,
                AuthActivityLog::LOGIN_FAILED,
                $matchedUser,
                $credentials['email'],
                $matchedUser ? 'invalid_password' : 'user_not_found',
                AuthActivityLog::STATUS_FAILED
            );

            return back()->withErrors([
                'email' => 'כתובת הדוא"ל או הסיסמה שגויים.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User|null $user */
        $user = Auth::user();

        $this->logAuthActivity(
            $request,
            AuthActivityLog::LOGIN_SUCCESS,
            $user,
            $user?->email ?? $credentials['email'],
            null,
            AuthActivityLog::STATUS_SUCCESS
        );

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        $this->logAuthActivity(
            $request,
            AuthActivityLog::LOGOUT,
            $user,
            $user?->email,
            null,
            AuthActivityLog::STATUS_SUCCESS
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function logAuthActivity(
        Request $request,
        string $eventType,
        ?User $user,
        ?string $email,
        ?string $failureReason,
        string $status,
    ): void {
        try {
            AuthActivityLog::create([
                'user_id' => $user?->id,
                'event_type' => $eventType,
                'status' => $status,
                'email' => $email,
                'failure_reason' => $failureReason,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
