<?php

namespace App\Http\Controllers;

use App\Models\AuthActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAdmin();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'event_type' => ['nullable', Rule::in(array_keys($this->eventOptions()))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logsQuery = AuthActivityLog::with('user')
            ->latest('occurred_at');

        $this->applyFilters($logsQuery, $filters);

        $summaryQuery = AuthActivityLog::query();
        $this->applyDateFilters($summaryQuery, $filters);

        $topUsersQuery = AuthActivityLog::query()
            ->join('users', 'users.id', '=', 'auth_activity_logs.user_id')
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw(
                "SUM(CASE WHEN auth_activity_logs.event_type = ? THEN 1 ELSE 0 END) as successful_logins",
                [AuthActivityLog::LOGIN_SUCCESS]
            )
            ->selectRaw(
                "SUM(CASE WHEN auth_activity_logs.event_type = ? THEN 1 ELSE 0 END) as failed_attempts",
                [AuthActivityLog::LOGIN_FAILED]
            )
            ->selectRaw('MAX(auth_activity_logs.occurred_at) as last_seen_at')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->havingRaw(
                "SUM(CASE WHEN auth_activity_logs.event_type IN (?, ?, ?) THEN 1 ELSE 0 END) > 0",
                [AuthActivityLog::LOGIN_SUCCESS, AuthActivityLog::LOGIN_FAILED, AuthActivityLog::LOGOUT]
            )
            ->orderByDesc('successful_logins')
            ->orderByDesc('last_seen_at')
            ->limit(10);

        $this->applyDateFilters($topUsersQuery, $filters);

        return view('auth-activity.index', [
            'logs' => $logsQuery->paginate(50)->withQueryString(),
            'eventOptions' => $this->eventOptions(),
            'failureReasonLabels' => $this->failureReasonLabels(),
            'summary' => [
                'successful_logins' => (clone $summaryQuery)
                    ->where('event_type', AuthActivityLog::LOGIN_SUCCESS)
                    ->count(),
                'failed_logins' => (clone $summaryQuery)
                    ->where('event_type', AuthActivityLog::LOGIN_FAILED)
                    ->count(),
                'logouts' => (clone $summaryQuery)
                    ->where('event_type', AuthActivityLog::LOGOUT)
                    ->count(),
                'active_users' => (clone $summaryQuery)
                    ->where('event_type', AuthActivityLog::LOGIN_SUCCESS)
                    ->whereNotNull('user_id')
                    ->distinct('user_id')
                    ->count('user_id'),
            ],
            'topUsers' => $topUsersQuery->get(),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'event_type' => $filters['event_type'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
        ]);
    }

    private function applyFilters($query, array $filters): void
    {
        $this->applyDateFilters($query, $filters);

        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['q'])) {
            $needle = trim((string) $filters['q']);

            $query->where(function ($innerQuery) use ($needle) {
                $innerQuery
                    ->where('email', 'like', "%{$needle}%")
                    ->orWhere('ip_address', 'like', "%{$needle}%")
                    ->orWhere('failure_reason', 'like', "%{$needle}%")
                    ->orWhereHas('user', function ($userQuery) use ($needle) {
                        $userQuery
                            ->where('name', 'like', "%{$needle}%")
                            ->orWhere('email', 'like', "%{$needle}%");
                    });
            });
        }
    }

    private function applyDateFilters($query, array $filters): void
    {
        if (! empty($filters['from'])) {
            $query->whereDate('occurred_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('occurred_at', '<=', $filters['to']);
        }
    }

    private function eventOptions(): array
    {
        return [
            AuthActivityLog::LOGIN_SUCCESS => 'התחברות מוצלחת',
            AuthActivityLog::LOGIN_FAILED => 'כשלון התחברות',
            AuthActivityLog::LOGOUT => 'התנתקות',
        ];
    }

    private function failureReasonLabels(): array
    {
        return [
            'invalid_password' => 'סיסמה שגויה',
            'user_not_found' => 'משתמש לא קיים',
            'validation_error' => 'שגיאת ולידציה',
        ];
    }
}
