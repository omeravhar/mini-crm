<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = $this->user();
        $now = now();

        $leadScope = Lead::query()->with('owner');
        $customerScope = Customer::query()->with('owner');
        $closedStatuses = LeadStatus::closedValues();

        if (! $user?->isAdmin()) {
            $leadScope->where(function ($query) use ($user) {
                $query->where('owner_id', $user?->id)
                    ->orWhere('created_by', $user?->id);
            });

            $customerScope->where('owner_id', $user?->id);
        }

        return view('dashboard', [
            'stats' => [
                'leads' => (clone $leadScope)->count(),
                'open_leads' => (clone $leadScope)->whereNotIn('status', $closedStatuses)->count(),
                'uncontacted_leads' => (clone $leadScope)
                    ->where(function ($query) {
                        $query->where('status', 'new')
                            ->orWhereNull('status');
                    })
                    ->count(),
                'customers' => (clone $customerScope)->count(),
                'users' => $user?->isAdmin() ? User::count() : null,
            ],
            'greeting' => $this->greetingPayload($now, $user?->name),
            'leadTrend' => $this->leadTrendPayload($leadScope, $now),
            'leadIndexRoute' => $user?->isAdmin()
                ? route('admin.leads.index')
                : route('leads.my'),
            'followUpRoute' => $user?->isAdmin()
                ? route('admin.leads.index', ['follow_up_scope' => 'upcoming'])
                : route('leads.my', ['follow_up_scope' => 'upcoming']),
            'customerIndexRoute' => route('customers.index'),
            'recentLeads' => $leadScope->latest()->take(6)->get(),
            'upcomingLeads' => (clone $leadScope)
                ->whereDate('follow_up', '>=', now()->toDateString())
                ->orderBy('follow_up')
                ->take(6)
                ->get(),
            'recentCustomers' => $customerScope->latest()->take(6)->get(),
        ]);
    }

    private function greetingPayload(Carbon $dateTime, ?string $fullName): array
    {
        $firstName = trim(explode(' ', trim((string) $fullName))[0] ?? '');

        if ($dateTime->hour < 12) {
            $prefix = 'בוקר טוב';
            $subtitle = 'הנה תמונת המצב של היום מתוך המערכת.';
        } elseif ($dateTime->hour < 18) {
            $prefix = 'צהריים טובים';
            $subtitle = 'כל מה שצריך כדי להישאר על הקצב של היום.';
        } else {
            $prefix = 'ערב טוב';
            $subtitle = 'סיכום מהיר של הפעילות והמעקבים להמשך היום.';
        }

        return [
            'title' => trim($prefix.($firstName !== '' ? ', '.$firstName : '')),
            'subtitle' => $subtitle,
        ];
    }

    private function leadTrendPayload(Builder $leadScope, Carbon $dateTime): array
    {
        $currentStart = $dateTime->copy()->subDays(6)->startOfDay();
        $currentEnd = $dateTime->copy()->endOfDay();
        $previousStart = $dateTime->copy()->subDays(13)->startOfDay();
        $previousEnd = $dateTime->copy()->subDays(7)->endOfDay();

        $currentCount = (clone $leadScope)
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->count();

        $previousCount = (clone $leadScope)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $difference = $currentCount - $previousCount;

        if ($difference === 0) {
            return [
                'direction' => 'flat',
                'label' => 'ללא שינוי מול השבוע שעבר',
            ];
        }

        $percentage = $previousCount > 0
            ? (int) round((abs($difference) / $previousCount) * 100)
            : 100;

        return [
            'direction' => $difference > 0 ? 'up' : 'down',
            'label' => sprintf('%d%% %s מהשבוע שעבר', $percentage, $difference > 0 ? 'יותר' : 'פחות'),
        ];
    }
}
