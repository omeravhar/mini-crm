<?php

namespace App\Http\Controllers;

use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeadStatusController extends Controller
{
    public function index()
    {
        $this->requireAdmin();

        return view('lead-statuses.index', [
            'statuses' => LeadStatus::query()
                ->withCount('leads')
                ->ordered()
                ->get(),
            'badgeOptions' => $this->badgeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin();

        $data = $this->validatedData($request);
        $data['slug'] = LeadStatus::normalizeSlug($data['slug'] ?? null, $data['name']);

        if (LeadStatus::where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'המזהה הזה כבר קיים.',
            ]);
        }

        LeadStatus::create([
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_system' => false,
            'is_closed' => $request->boolean('is_closed'),
        ]);

        return redirect()
            ->route('admin.lead-statuses.index')
            ->with('success', 'הסטטוס נוצר בהצלחה.');
    }

    public function update(Request $request, LeadStatus $leadStatus)
    {
        $this->requireAdmin();

        $data = $this->validatedData($request, $leadStatus);
        unset($data['slug']);

        $leadStatus->update([
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_closed' => $request->boolean('is_closed'),
        ]);

        return redirect()
            ->route('admin.lead-statuses.index')
            ->with('success', 'הסטטוס עודכן בהצלחה.');
    }

    public function destroy(LeadStatus $leadStatus)
    {
        $this->requireAdmin();

        if ($leadStatus->is_system) {
            return back()->withErrors([
                'status' => 'לא ניתן למחוק סטטוס מערכת.',
            ]);
        }

        if ($leadStatus->leads()->exists()) {
            return back()->withErrors([
                'status' => 'לא ניתן למחוק סטטוס שיש לידים שמשתמשים בו.',
            ]);
        }

        $leadStatus->delete();

        return redirect()
            ->route('admin.lead-statuses.index')
            ->with('success', 'הסטטוס נמחק בהצלחה.');
    }

    private function validatedData(Request $request, ?LeadStatus $leadStatus = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                $leadStatus ? 'prohibited' : 'nullable',
                'string',
                'max:80',
                'alpha_dash:ascii',
                Rule::unique('lead_statuses', 'slug')->ignore($leadStatus?->id),
            ],
            'badge_class' => ['required', Rule::in(array_keys($this->badgeOptions()))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_closed' => ['nullable', 'boolean'],
        ]);
    }

    private function badgeOptions(): array
    {
        return [
            'text-bg-secondary' => 'אפור',
            'text-bg-primary' => 'כחול',
            'text-bg-info' => 'תכלת',
            'text-bg-warning' => 'צהוב',
            'text-bg-success' => 'ירוק',
            'text-bg-danger' => 'אדום',
            'text-bg-dark' => 'כהה',
        ];
    }
}
