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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    private const ARCHIVE_REASON_REQUIRED_STATUSES = [
        'lost',
    ];

    private const OPEN_STATUS_FILTER = '__open__';

    private const SUMMARY_SCOPES = [
        'unassigned',
        'converted',
    ];

    private const ENTRY_TIMEZONE = 'Asia/Jerusalem';

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
        $data = $this->validatedLeadData($request, null, $admin);
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

    public function archive(Request $request)
    {
        return view('leads.archive', $this->archivedLeadsData($request, $this->user()));
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
        $data = $this->validatedLeadData($request, $lead, $user);
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

    public function bulkAssign(Request $request)
    {
        $this->requireAdmin();

        $userIds = User::query()
            ->pluck('id')
            ->map(fn (int $id) => (string) $id)
            ->all();

        $data = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['required', 'integer', 'distinct', 'exists:leads,id'],
            'owner_id' => ['required', Rule::in(array_merge(['unassigned'], $userIds))],
        ]);

        $leadIds = collect($data['lead_ids'])
            ->map(fn (int|string $id) => (int) $id)
            ->unique()
            ->values();

        $ownerId = $data['owner_id'] === 'unassigned'
            ? null
            : (int) $data['owner_id'];

        $ownerName = $ownerId
            ? User::query()->whereKey($ownerId)->value('name')
            : 'ללא שיוך';

        DB::transaction(function () use ($leadIds, $ownerId) {
            Lead::query()
                ->whereKey($leadIds->all())
                ->get()
                ->each(function (Lead $lead) use ($ownerId) {
                    $previousOwnerId = $lead->owner_id;
                    $previousScheduleSignature = $this->followUpSignature($lead);

                    $lead->update([
                        'owner_id' => $ownerId,
                    ]);
                    $lead->load('owner');

                    $this->syncFollowUpArtifacts($lead, $previousOwnerId, $previousScheduleSignature);
                    $this->notifyLeadAssignment($lead, $previousOwnerId);
                });
        });

        $message = sprintf(
            'שיוך של %d לידים עודכן בהצלחה ל%s.',
            $leadIds->count(),
            $ownerName
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'lead_ids' => $leadIds->all(),
                'owner_id' => $ownerId,
                'owner_name' => $ownerName,
                'count' => $leadIds->count(),
            ]);
        }

        return back()->with('success', $message);
    }

    public function quickUpdate(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $previousOwnerId = $lead->owner_id;
        $previousScheduleSignature = $this->followUpSignature($lead);

        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in($this->statusValues())],
            'priority' => ['sometimes', 'required', Rule::in(self::PRIORITIES)],
            'archive_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! array_key_exists('status', $data)) {
            unset($data['archive_reason']);
        }

        abort_if(empty($data), 422, 'No lead fields were provided for update.');

        if (array_key_exists('status', $data)) {
            $this->ensureArchiveReasonIsPresent($data['status'], $data['archive_reason'] ?? null);
            $this->syncClosedAtPayload($data, $lead);
            $this->syncArchivePayload($data, $lead, $this->user());
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
                'archived_at' => optional($lead->archived_at)->format('Y-m-d H:i'),
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

    private function validatedLeadData(Request $request, ?Lead $lead = null, ?User $actor = null): array
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
            'archive_reason' => ['nullable', 'string', 'max:1000'],
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

        $this->ensureArchiveReasonIsPresent($data['status'], $data['archive_reason'] ?? null);

        $data['tags'] = collect(explode(',', (string) ($data['tags_text'] ?? '')))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
        unset($data['tags_text']);

        $this->syncClosedAtPayload($data, $lead);
        $this->syncArchivePayload($data, $lead, $actor);

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
        $query = Lead::with(['owner', 'customer'])
            ->whereNull('archived_at');
        $summaryFilters = array_merge($filters, ['summary_scope' => '']);
        $summaryQuery = Lead::query()->whereNull('archived_at');

        $this->applyLeadFilters($query, $filters, true);
        $this->applyLeadFilters($summaryQuery, $summaryFilters, true);

        return [
            'leads' => $query->latest()->get(),
            'leadSummary' => [
                'total' => (clone $summaryQuery)->count(),
                'unassigned' => (clone $summaryQuery)->whereNull('owner_id')->count(),
                'converted' => (clone $summaryQuery)->whereHas('customer')->count(),
            ],
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
            ->where('owner_id', $userId)
            ->whereNull('archived_at');

        $this->applyLeadFilters($query, $filters, false);

        return [
            'leads' => $query->latest()->get(),
            'options' => $this->options(),
            'statusLabels' => $this->statusLabels(),
            'filters' => $filters,
            'campaignOptions' => $this->campaignOptions($userId, true),
        ];
    }

    private function archivedLeadsData(Request $request, ?User $user = null): array
    {
        $isAdmin = $user?->isAdmin() ?? false;
        $filters = $this->leadFilters($request, true);
        $query = Lead::with(['owner', 'customer', 'creator', 'archiver'])
            ->whereNotNull('archived_at');
        $summaryFilters = array_merge($filters, ['status' => '']);
        $summaryQuery = Lead::query()->whereNotNull('archived_at');

        $this->applyLeadFilters($query, $filters, true);
        $this->applyLeadFilters($summaryQuery, $summaryFilters, true);

        return [
            'leads' => $query->orderByDesc('archived_at')->orderByDesc('id')->get(),
            'archiveSummary' => [
                'total' => (clone $summaryQuery)->count(),
                'won' => (clone $summaryQuery)->where('status', 'won')->count(),
                'lost' => (clone $summaryQuery)->where('status', 'lost')->count(),
            ],
            'users' => User::orderBy('name')->get(),
            'options' => $this->options(),
            'statusLabels' => $this->statusLabels(),
            'filters' => $filters,
            'campaignOptions' => $this->campaignOptions(null, false, true),
            'archiveRoute' => route('leads.archive'),
            'activeLeadsRoute' => route($isAdmin ? 'admin.leads.index' : 'leads.my'),
            'isAdminArchive' => $isAdmin,
        ];
    }

    private function campaignOptions(?int $ownerId = null, bool $restrictToOwner = false, bool $archivedOnly = false, ?User $accessibleTo = null): array
    {
        if ($restrictToOwner && ! $ownerId) {
            return [];
        }

        $leads = Lead::query()
            ->when($restrictToOwner, fn (Builder $query) => $query->where('owner_id', $ownerId))
            ->when($accessibleTo && ! $accessibleTo->isAdmin(), function (Builder $query) use ($accessibleTo) {
                $query->where(function (Builder $builder) use ($accessibleTo) {
                    $builder
                        ->where('owner_id', $accessibleTo->id)
                        ->orWhere('created_by', $accessibleTo->id);
                });
            })
            ->when($archivedOnly, fn (Builder $query) => $query->whereNotNull('archived_at'))
            ->when(! $archivedOnly, fn (Builder $query) => $query->whereNull('archived_at'))
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
            'entry_from' => $this->dateFilterValue($request->input('entry_from')),
            'entry_to' => $this->dateFilterValue($request->input('entry_to')),
            'follow_up_scope' => (string) $request->input('follow_up_scope', ''),
            'summary_scope' => $allowOwnerFilter ? (string) $request->input('summary_scope', '') : '',
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

        if ($filters['status'] === self::OPEN_STATUS_FILTER) {
            $query->whereNotIn('status', $this->closedStatuses());
        } elseif (in_array($filters['status'], $this->statusValues(), true)) {
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

        if ($filters['entry_from'] !== '' || $filters['entry_to'] !== '') {
            $this->applyEntryDateFilter($query, $filters['entry_from'], $filters['entry_to']);
        }

        $today = Carbon::now(config('app.timezone'))->toDateString();

        match ($filters['follow_up_scope']) {
            'today' => $query->whereDate('follow_up', $today),
            'upcoming' => $query->whereDate('follow_up', '>', $today),
            'overdue' => $query->whereDate('follow_up', '<', $today),
            'none' => $query->whereNull('follow_up'),
            default => null,
        };

        if ($allowOwnerFilter && in_array($filters['summary_scope'] ?? '', self::SUMMARY_SCOPES, true)) {
            match ($filters['summary_scope']) {
                'unassigned' => $query->whereNull('owner_id'),
                'converted' => $query->whereHas('customer'),
                default => null,
            };
        }
    }

    private function dateFilterValue(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value, self::ENTRY_TIMEZONE);
        } catch (\Throwable) {
            return '';
        }

        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function applyEntryDateFilter(Builder $query, string $from, string $to): void
    {
        if ($from !== '') {
            $fromDate = Carbon::createFromFormat('Y-m-d', $from, self::ENTRY_TIMEZONE)
                ->startOfDay()
                ->timezone(config('app.timezone'))
                ->toDateTimeString();

            $query->whereRaw('COALESCE(received_at, created_at) >= ?', [$fromDate]);
        }

        if ($to !== '') {
            $toDate = Carbon::createFromFormat('Y-m-d', $to, self::ENTRY_TIMEZONE)
                ->endOfDay()
                ->timezone(config('app.timezone'))
                ->toDateTimeString();

            $query->whereRaw('COALESCE(received_at, created_at) <= ?', [$toDate]);
        }
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

    private function syncArchivePayload(array &$data, ?Lead $lead = null, ?User $actor = null): void
    {
        $status = (string) ($data['status'] ?? $lead?->status ?? '');

        if ($status === '') {
            return;
        }

        if (! in_array($status, $this->closedStatuses(), true)) {
            $data['archived_at'] = null;
            $data['archived_by'] = null;
            $data['archive_reason'] = null;

            return;
        }

        $data['archived_at'] = $lead?->archived_at
            ?? $lead?->closed_at
            ?? $data['closed_at']
            ?? now();

        $data['archived_by'] = $lead?->archived_by ?? $actor?->id;
        $data['archive_reason'] = $this->requiresArchiveReason($status)
            ? trim((string) ($data['archive_reason'] ?? $lead?->archive_reason ?? ''))
            : null;
    }

    private function ensureArchiveReasonIsPresent(?string $status, mixed $reason): void
    {
        if (! $this->requiresArchiveReason((string) $status)) {
            return;
        }

        if (trim((string) $reason) !== '') {
            return;
        }

        throw ValidationException::withMessages([
            'archive_reason' => 'יש למלא סיבה לפני העברת הליד לארכיון.',
        ]);
    }

    private function requiresArchiveReason(string $status): bool
    {
        return in_array($status, self::ARCHIVE_REASON_REQUIRED_STATUSES, true);
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
