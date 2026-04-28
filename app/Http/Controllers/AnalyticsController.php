<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAdmin();
        $closedStatuses = LeadStatus::closedValues();
        $statusLabels = LeadStatus::labels();

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $timezone = config('app.timezone');
        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'], $timezone)->endOfDay()
            : Carbon::now($timezone)->endOfDay();
        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'], $timezone)->startOfDay()
            : $to->copy()->subDays(29)->startOfDay();

        $createdLeads = Lead::with('owner')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $closedLeads = Lead::with('owner')
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [$from, $to])
            ->get();

        $wonLeads = $closedLeads->where('status', 'won')->values();
        $lostLeads = $closedLeads->where('status', 'lost')->values();
        $openLeads = $createdLeads->reject(fn (Lead $lead) => in_array($lead->status, $closedStatuses, true))->values();

        $averageCloseDays = round((float) $closedLeads->avg(function (Lead $lead) {
            if (! $lead->closed_at) {
                return null;
            }

            return $lead->created_at->diffInMinutes($lead->closed_at) / 1440;
        }), 1);

        $users = User::orderBy('name')->get();
        $ownerPerformance = $users
            ->map(function (User $user) use ($createdLeads, $closedLeads, $closedStatuses) {
                $createdByOwner = $createdLeads->where('owner_id', $user->id)->values();
                $closedByOwner = $closedLeads->where('owner_id', $user->id)->values();
                $wonByOwner = $closedByOwner->where('status', 'won')->values();
                $lostByOwner = $closedByOwner->where('status', 'lost')->values();
                $openByOwner = $createdByOwner->reject(fn (Lead $lead) => in_array($lead->status, $closedStatuses, true))->values();

                if ($createdByOwner->isEmpty() && $closedByOwner->isEmpty()) {
                    return null;
                }

                $avgCloseDays = round((float) $closedByOwner->avg(function (Lead $lead) {
                    if (! $lead->closed_at) {
                        return null;
                    }

                    return $lead->created_at->diffInMinutes($lead->closed_at) / 1440;
                }), 1);

                return [
                    'name' => $user->name,
                    'created' => $createdByOwner->count(),
                    'open' => $openByOwner->count(),
                    'won' => $wonByOwner->count(),
                    'lost' => $lostByOwner->count(),
                    'avg_close_days' => $closedByOwner->isNotEmpty() ? $avgCloseDays : null,
                ];
            })
            ->filter()
            ->values();

        $statusDistribution = collect(array_keys($statusLabels))
            ->map(fn (string $status) => $createdLeads->where('status', $status)->count())
            ->values();

        $dailyLabels = [];
        $dailyCreated = [];
        $dailyWon = [];
        $dailyLost = [];

        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $dailyLabels[] = $cursor->format('d/m');
            $dailyCreated[] = $createdLeads->filter(fn (Lead $lead) => $lead->created_at->isSameDay($cursor))->count();
            $dailyWon[] = $wonLeads->filter(fn (Lead $lead) => $lead->closed_at?->isSameDay($cursor))->count();
            $dailyLost[] = $lostLeads->filter(fn (Lead $lead) => $lead->closed_at?->isSameDay($cursor))->count();
            $cursor->addDay();
        }

        return view('analytics.index', [
            'filters' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'metrics' => [
                'created_leads' => $createdLeads->count(),
                'open_leads' => $openLeads->count(),
                'won_leads' => $wonLeads->count(),
                'closed_leads' => $closedLeads->count(),
                'average_close_days' => $closedLeads->isNotEmpty() ? $averageCloseDays : null,
                'win_rate' => $closedLeads->isNotEmpty()
                    ? round(($wonLeads->count() / max($closedLeads->count(), 1)) * 100, 1)
                    : null,
            ],
            'ownerPerformance' => $ownerPerformance,
            'charts' => [
                'daily' => [
                    'labels' => $dailyLabels,
                    'created' => $dailyCreated,
                    'won' => $dailyWon,
                    'lost' => $dailyLost,
                ],
                'statusDistribution' => [
                    'labels' => array_values($statusLabels),
                    'values' => $statusDistribution->all(),
                ],
                'owners' => [
                    'labels' => $ownerPerformance->pluck('name')->all(),
                    'open' => $ownerPerformance->pluck('open')->all(),
                    'won' => $ownerPerformance->pluck('won')->all(),
                    'lost' => $ownerPerformance->pluck('lost')->all(),
                ],
            ],
        ]);
    }
}
