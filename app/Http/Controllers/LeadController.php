<?php

namespace App\Http\Controllers;

use App\Mail\LeadFollowUpInviteMail;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use App\Notifications\LeadFollowUpScheduledNotification;
use App\Support\LeadAssignmentNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    private const SOURCES = [
        'website',
        'inbound_call',
        'outbound',
        'referral',
        'event',
        'social',
        'partner',
    ];

    private const PRIORITIES = [
        'low',
        'medium',
        'high',
    ];

    private const LEAD_TYPES = [
        'new',
        'returning',
    ];

    private const PIPELINES = [
        'default',
        'enterprise',
        'smb',
    ];

    private const STAGES = [
        'lead',
        'mql',
        'sql',
        'negotiation',
        'won',
    ];

    private const VISIBILITY = [
        'team',
        'private',
    ];

    public function create()
    {
        $this->requireAdmin();

        return view('leads.form', [
            'lead' => new Lead([
                'status' => 'new',
                'priority' => 'medium',
                'lead_type' => 'new',
                'pipeline' => 'default',
                'stage' => 'lead',
                'visibility' => 'team',
            ]),
            'users' => User::orderBy('name')->get(),
            'options' => $this->options(),
            'statusLabels' => $this->statusLabels(),
            'isEditing' => false,
        ]);
    }

    public function store(Request $request)
    {
        $admin = $this->requireAdmin();
        $data = $this->validatedLeadData($request);
        $data['created_by'] = $admin->id;

        $lead = Lead::create($data);
        $lead->load('owner');

        $this->syncFollowUpArtifacts($lead);
        $this->notifyLeadAssignment($lead);

        $route = $request->boolean('save_new')
            ? route('admin.leads.create')
            : route('admin.leads.index');

        return redirect($route)->with('success', 'הליד נשמר בהצלחה.');
    }

    public function index(Request $request)
    {
        $this->requireAdmin();
        $data = $this->adminIndexData($request);

        if ($request->boolean('fragment')) {
            return response()->json([
                'html' => view('leads.partials.admin-content', $data)->render(),
            ]);
        }

        return view('leads.index', $data);
    }

    public function myLeads(Request $request)
    {
        $user = $this->user();
        $data = $this->myLeadsData($user?->id, $request);

        if ($request->boolean('fragment')) {
            return response()->json([
                'html' => view('leads.partials.my-content', $data)->render(),
            ]);
        }

        return view('leads.my', $data);
    }

    public function edit(Lead $lead)
    {
        $this->authorizeLead($lead);

        return view('leads.form', [
            'lead' => $lead,
            'users' => User::orderBy('name')->get(),
            'options' => $this->options(),
            'statusLabels' => $this->statusLabels(),
            'isEditing' => true,
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $user = $this->authorizeLead($lead);
        $data = $this->validatedLeadData($request, $lead);
        $previousOwnerId = $lead->owner_id;
        $previousScheduleSignature = $this->followUpSignature($lead);

        if (! $user->isAdmin()) {
            unset($data['owner_id']);
        }

        $lead->update($data);
        $lead->load('owner');

        $this->syncFollowUpArtifacts($lead, $previousOwnerId, $previousScheduleSignature);
        $this->notifyLeadAssignment($lead, $previousOwnerId);

        return redirect()
            ->route($user->isAdmin() ? 'admin.leads.index' : 'leads.my')
            ->with('success', 'הליד עודכן בהצלחה.');
    }

    public function destroy(Lead $lead)
    {
        $user = $this->authorizeLead($lead);

        if ($lead->attachment_path) {
            Storage::disk('public')->delete($lead->attachment_path);
        }

        $lead->delete();

        return redirect()
            ->route($user->isAdmin() ? 'admin.leads.index' : 'leads.my')
            ->with('success', 'הליד נמחק בהצלחה.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $this->requireAdmin();
        $previousOwnerId = $lead->owner_id;
        $previousScheduleSignature = $this->followUpSignature($lead);

        $data = $request->validate([
            'owner_id' => ['nullable', 'exists:users,id'],
        ]);

        $lead->update($data);
        $lead->load('owner');

        $this->syncFollowUpArtifacts($lead, $previousOwnerId, $previousScheduleSignature);
        $this->notifyLeadAssignment($lead, $previousOwnerId);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'שיוך הליד עודכן בהצלחה.',
                'lead_id' => $lead->id,
                'owner_id' => $lead->owner_id,
                'owner_name' => $lead->owner?->name,
            ]);
        }

        return back()->with('success', 'שיוך הליד עודכן בהצלחה.');
    }

    public function quickUpdate(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $previousOwnerId = $lead->owner_id;
        $previousScheduleSignature = $this->followUpSignature($lead);

        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in($this->statusValues())],
            'priority' => ['sometimes', 'required', Rule::in(self::PRIORITIES)],
        ]);

        abort_if(empty($data), 422, 'No lead fields were provided for update.');

        if (array_key_exists('status', $data)) {
            $this->syncClosedAtPayload($data, $lead);
        }

        $lead->update($data);
        $lead->load('owner');

        if (array_key_exists('status', $data)) {
            $this->syncFollowUpArtifacts($lead, $previousOwnerId, $previousScheduleSignature);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'פרטי הליד עודכנו בהצלחה.',
                'lead_id' => $lead->id,
                'status' => $lead->status,
                'priority' => $lead->priority,
                'closed_at' => optional($lead->closed_at)->format('Y-m-d H:i'),
            ]);
        }

        return back()->with('success', 'פרטי הליד עודכנו בהצלחה.');
    }

    public function convertToCustomer(Lead $lead)
    {
        $this->authorizeLead($lead);

        if (blank($lead->email)) {
            return back()->withErrors([
                'email' => 'לא ניתן להמיר ליד ללקוח בלי כתובת דוא"ל.',
            ]);
        }

        Customer::updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'owner_id' => $lead->owner_id,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'job_title' => $lead->job_title,
                'website' => $lead->website,
                'street' => $lead->street,
                'zip' => $lead->zip,
                'city' => $lead->city,
                'country' => $lead->country,
                'notes' => $lead->notes,
            ],
        );

        $lead->update([
            'converted_to_customer_at' => now(),
            'status' => $lead->status === 'new' ? 'qualified' : $lead->status,
        ]);

        return back()->with('success', 'הליד הומר ללקוח בהצלחה.');
    }

    private function authorizeLead(Lead $lead): User
    {
        $user = $this->user();

        abort_unless(
            $user?->isAdmin()
            || $lead->owner_id === $user?->id
            || $lead->created_by === $user?->id,
            403
        );

        return $user;
    }

    private function validatedLeadData(Request $request, ?Lead $lead = null): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:150'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'website' => ['nullable', 'url', 'max:255'],
            'source' => ['nullable', Rule::in(self::SOURCES)],
            'status' => ['required', Rule::in($this->statusValues())],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'expected_value' => ['nullable', 'numeric', 'min:0'],
            'interested_in' => ['nullable', 'string', 'max:255'],
            'lead_type' => ['nullable', Rule::in(self::LEAD_TYPES)],
            'external_campaign_name' => ['nullable', 'string', 'max:255'],
            'follow_up' => ['nullable', 'date', 'required_with:follow_up_time'],
            'follow_up_time' => ['nullable', 'date_format:H:i', 'required_with:follow_up'],
            'tags_text' => ['nullable', 'string', 'max:500'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
            'remove_attachment' => ['nullable', 'boolean'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'pipeline' => ['required', Rule::in(self::PIPELINES)],
            'stage' => ['required', Rule::in(self::STAGES)],
            'visibility' => ['required', Rule::in(self::VISIBILITY)],
        ]);

        $data['tags'] = collect(explode(',', (string) ($data['tags_text'] ?? '')))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
        unset($data['tags_text']);

        $this->syncClosedAtPayload($data, $lead);

        $data['lead_type'] = $data['lead_type'] ?? $lead?->lead_type ?? 'new';

        if (empty($data['follow_up'])) {
            $data['follow_up_time'] = null;
        }

        if ($request->boolean('remove_attachment') && $lead?->attachment_path) {
            Storage::disk('public')->delete($lead->attachment_path);
            $data['attachment_path'] = null;
        }

        if ($request->hasFile('attachment')) {
            if ($lead?->attachment_path) {
                Storage::disk('public')->delete($lead->attachment_path);
            }

            $data['attachment_path'] = $request->file('attachment')->store('lead-attachments', 'public');
        }

        return $data;
    }

    private function options(): array
    {
        return [
            'sources' => self::SOURCES,
            'statuses' => $this->statusValues(),
            'priorities' => self::PRIORITIES,
            'lead_types' => self::LEAD_TYPES,
            'pipelines' => self::PIPELINES,
            'stages' => self::STAGES,
            'visibility' => self::VISIBILITY,
        ];
    }

    private function adminIndexData(Request $request): array
    {
        $filters = $this->leadFilters($request, true);
        $query = Lead::with(['owner', 'customer']);

        $this->applyLeadFilters($query, $filters, true);

        return [
            'leads' => $query->latest()->get(),
            'users' => User::orderBy('name')->get(),
            'options' => $this->options(),
            'statusLabels' => $this->statusLabels(),
            'filters' => $filters,
            'campaignOptions' => $this->campaignOptions(),
        ];
    }

    private function myLeadsData(?int $userId, Request $request): array
    {
        $filters = $this->leadFilters($request, false);
        $query = Lead::with(['owner', 'customer'])
            ->where('owner_id', $userId);

        $this->applyLeadFilters($query, $filters, false);

        return [
            'leads' => $query->latest()->get(),
            'options' => $this->options(),
            'statusLabels' => $this->statusLabels(),
            'filters' => $filters,
            'campaignOptions' => $this->campaignOptions($userId, true),
        ];
    }

    private function campaignOptions(?int $ownerId = null, bool $restrictToOwner = false): array
    {
        if ($restrictToOwner && ! $ownerId) {
            return [];
        }

        $leads = Lead::query()
            ->when($restrictToOwner, fn (Builder $query) => $query->where('owner_id', $ownerId))
            ->where(function (Builder $query) {
                $query
                    ->whereNotNull('external_campaign_name')
                    ->orWhereNotNull('external_campaign_id');
            })
            ->get(['external_campaign_name', 'external_campaign_id']);

        $options = [];

        foreach ($leads as $lead) {
            $value = trim((string) ($lead->external_campaign_name ?: $lead->external_campaign_id));
            $label = $lead->campaign_display;

            if ($value === '' || ! $label || array_key_exists($value, $options)) {
                continue;
            }

            $options[$value] = $label;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    private function followUpSignature(Lead $lead): ?string
    {
        $followUpAt = $lead->follow_up_at;

        if (! $followUpAt) {
            return null;
        }

        return implode('|', [
            $lead->owner_id,
            $followUpAt->format('Y-m-d H:i'),
        ]);
    }

    private function syncFollowUpArtifacts(Lead $lead, ?int $previousOwnerId = null, ?string $previousSignature = null): void
    {
        $lead->loadMissing('owner');

        $followUpAt = $lead->follow_up_at;
        $currentSignature = $this->followUpSignature($lead);
        $today = Carbon::now(config('app.timezone'))->startOfDay();
        $isClosedStatus = in_array($lead->status, $this->closedStatuses(), true);

        if ($previousSignature !== null && $previousSignature === $currentSignature && ! $isClosedStatus) {
            return;
        }

        collect([$previousOwnerId, $lead->owner_id])
            ->filter()
            ->unique()
            ->each(fn (int $ownerId) => $this->deleteLeadFollowUpNotifications($ownerId, $lead->id));

        if ($isClosedStatus) {
            return;
        }

        if (! $followUpAt || ! $lead->owner || $followUpAt->lt($today)) {
            return;
        }

        $lead->owner->notify(new LeadFollowUpScheduledNotification($lead, $followUpAt));

        if ($lead->owner->email && $followUpAt->gt(Carbon::now(config('app.timezone')))) {
            Mail::to($lead->owner->email)->queue(
                new LeadFollowUpInviteMail($lead, $lead->owner, $followUpAt)
            );
        }
    }

    private function deleteLeadFollowUpNotifications(int $ownerId, int $leadId): void
    {
        $owner = User::find($ownerId);

        if (! $owner) {
            return;
        }

        $owner->notifications()
            ->where('type', LeadFollowUpScheduledNotification::class)
            ->where('data->lead_id', $leadId)
            ->delete();
    }

    private function notifyLeadAssignment(Lead $lead, ?int $previousOwnerId = null): void
    {
        app(LeadAssignmentNotifier::class)->notifyIfAssigned($lead, $previousOwnerId);
    }

    private function leadFilters(Request $request, bool $allowOwnerFilter): array
    {
        return [
            'q' => trim((string) $request->input('q', '')),
            'status' => (string) $request->input('status', ''),
            'priority' => (string) $request->input('priority', ''),
            'lead_type' => (string) $request->input('lead_type', ''),
            'owner_id' => $allowOwnerFilter ? (string) $request->input('owner_id', '') : '',
            'campaign' => trim((string) $request->input('campaign', '')),
            'follow_up_scope' => (string) $request->input('follow_up_scope', ''),
        ];
    }

    private function applyLeadFilters(Builder $query, array $filters, bool $allowOwnerFilter): void
    {
        if ($filters['q'] !== '') {
            $term = '%' . $filters['q'] . '%';
            $tokens = collect(preg_split('/\s+/', $filters['q']))
                ->filter()
                ->values();

            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('company', 'like', $term)
                    ->orWhere('interested_in', 'like', $term)
                    ->orWhere('external_campaign_name', 'like', $term)
                    ->orWhere('external_campaign_id', 'like', $term);
            });

            if ($tokens->count() > 1) {
                $query->where(function (Builder $builder) use ($tokens) {
                    foreach ($tokens as $token) {
                        $likeToken = '%' . $token . '%';

                        $builder->where(function (Builder $tokenQuery) use ($likeToken) {
                            $tokenQuery
                                ->where('first_name', 'like', $likeToken)
                                ->orWhere('last_name', 'like', $likeToken);
                        });
                    }
                });
            }
        }

        if (in_array($filters['status'], $this->statusValues(), true)) {
            $query->where('status', $filters['status']);
        }

        if (in_array($filters['priority'], self::PRIORITIES, true)) {
            $query->where('priority', $filters['priority']);
        }

        if (in_array($filters['lead_type'], self::LEAD_TYPES, true)) {
            $query->where('lead_type', $filters['lead_type']);
        }

        if ($allowOwnerFilter && $filters['owner_id'] !== '') {
            if ($filters['owner_id'] === 'unassigned') {
                $query->whereNull('owner_id');
            } elseif (ctype_digit($filters['owner_id'])) {
                $query->where('owner_id', (int) $filters['owner_id']);
            }
        }

        if ($filters['campaign'] !== '') {
            $query->where(function (Builder $builder) use ($filters) {
                $builder
                    ->where('external_campaign_name', $filters['campaign'])
                    ->orWhere('external_campaign_id', $filters['campaign']);
            });
        }

        $today = Carbon::now(config('app.timezone'))->toDateString();

        match ($filters['follow_up_scope']) {
            'today' => $query->whereDate('follow_up', $today),
            'upcoming' => $query->whereDate('follow_up', '>', $today),
            'overdue' => $query->whereDate('follow_up', '<', $today),
            'none' => $query->whereNull('follow_up'),
            default => null,
        };
    }

    private function syncClosedAtPayload(array &$data, ?Lead $lead = null): void
    {
        $closedStatuses = $this->closedStatuses();
        $isClosedStatus = in_array($data['status'], $closedStatuses, true);
        $wasClosedStatus = $lead ? in_array($lead->status, $closedStatuses, true) : false;

        if (! $isClosedStatus) {
            $data['closed_at'] = null;

            return;
        }

        if (! $lead || ! $wasClosedStatus) {
            $data['closed_at'] = now();

            return;
        }

        if ($lead->closed_at) {
            $data['closed_at'] = $lead->closed_at;

            return;
        }

        $data['closed_at'] = now();
    }

    private function statusValues(): array
    {
        return LeadStatus::values();
    }

    private function statusLabels(): array
    {
        return LeadStatus::labels();
    }

    private function closedStatuses(): array
    {
        return LeadStatus::closedValues();
    }
}
