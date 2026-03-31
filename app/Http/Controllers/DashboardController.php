<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = $this->user();

        $leadScope = Lead::query()->with('owner');
        $customerScope = Customer::query()->with('owner');

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
                'open_leads' => (clone $leadScope)->whereNotIn('status', ['won', 'lost'])->count(),
                'customers' => (clone $customerScope)->count(),
                'users' => $user?->isAdmin() ? User::count() : null,
            ],
            'recentLeads' => $leadScope->latest()->take(6)->get(),
            'upcomingLeads' => (clone $leadScope)
                ->whereDate('follow_up', '>=', now()->toDateString())
                ->orderBy('follow_up')
                ->take(6)
                ->get(),
            'recentCustomers' => $customerScope->latest()->take(6)->get(),
        ]);
    }
}
