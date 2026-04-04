<?php

namespace App\Http\Controllers;

use App\Concerns\AppliesCallCenterScope;
use App\Enums\UserRole;
use App\Http\Requests\CallCenterIndexRequest;
use App\Models\VoterRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CallCenterController extends Controller
{
    use AppliesCallCenterScope;

    private const DEFAULT_PER_PAGE = 15;

    public function index(CallCenterIndexRequest $request): Response
    {
        $validated = $request->validated();
        $user = $request->user();
        $search = trim((string) ($validated['search'] ?? ''));
        $ccFilter = $validated['cc_filter'] ?? '';
        $agentFilter = trim((string) ($validated['agent_filter'] ?? ''));
        $voteStatusFilter = array_values(array_filter(
            array_map('trim', explode(',', (string) ($validated['vote_status_filter'] ?? '')))
        ));
        $includeVoted = filter_var($validated['include_voted'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dhaairaFilter = trim((string) ($validated['dhaaira_filter'] ?? ''));
        $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : self::DEFAULT_PER_PAGE;
        $page = max(1, (int) $request->query('page', 1));

        $terms = $search !== ''
            ? collect(explode(',', $search))->map(fn ($t) => trim($t))->filter()->all()
            : [];

        $voters = $this->applyCcScope(VoterRecord::query(), $user)
            ->when(
                ! $includeVoted,
                fn ($q) => $q->where(fn ($q) => $q->whereNull('vote_status')->orWhere('vote_status', '!=', 'voted'))
            )
            ->when($terms !== [], function ($query) use ($terms) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($inner) use ($term) {
                            $inner->where('id_card_number', 'like', "%{$term}%")
                                ->orWhere('name', 'like', "%{$term}%")
                                ->orWhere('address', 'like', "%{$term}%")
                                ->orWhere('mobile', 'like', "%{$term}%");
                        });
                    }
                });
            })
            ->when($ccFilter === 'filled', fn ($q) => $q->whereNotNull('cc_remarks')->where('cc_remarks', '!=', ''))
            ->when($ccFilter === 'blank', fn ($q) => $q->where(fn ($q) => $q->whereNull('cc_remarks')->orWhere('cc_remarks', '')))
            ->when($agentFilter !== '', fn ($q) => $q->where('agent', $agentFilter))
            ->when($voteStatusFilter !== [], fn ($q) => $q->whereIn('vote_status', $voteStatusFilter))
            ->when($dhaairaFilter !== '', fn ($q) => $q->where('dhaairaa', $dhaairaFilter))
            ->select([
                'id',
                'list_number',
                'id_card_number',
                'name',
                'address',
                'mobile',
                'registered_box',
                'agent',
                'vote_status',
                'cc_remarks',
                'photo_path',
            ])
            ->orderBy('list_number')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString()
            ->through(fn (VoterRecord $voter) => [
                'id' => $voter->id,
                'list_number' => $voter->list_number,
                'id_card_number' => $voter->id_card_number,
                'name' => $voter->name,
                'address' => $voter->address,
                'mobile' => $voter->mobile,
                'registered_box' => $voter->registered_box,
                'agent' => $voter->agent,
                'vote_status' => $voter->vote_status,
                'cc_remarks' => $voter->cc_remarks,
                'photo_url' => $voter->photo_path !== null
                    ? Storage::disk('public')->url($voter->photo_path)
                    : null,
            ]);

        $agents = $this->applyCcScope(VoterRecord::query(), $user)
            ->whereNotNull('agent')
            ->where('agent', '!=', '')
            ->distinct()
            ->orderBy('agent')
            ->pluck('agent');

        $voteStatuses = $this->applyCcScope(VoterRecord::query(), $user)
            ->whereNotNull('vote_status')
            ->where('vote_status', '!=', '')
            ->distinct()
            ->orderBy('vote_status')
            ->pluck('vote_status');

        $showDhaairaFilter = $user !== null && (
            $user->hasRole(UserRole::CcMayor->value) || $user->hasRole(UserRole::CcRaeesa->value)
        );

        $dhaairas = $showDhaairaFilter
            ? $this->applyCcScope(VoterRecord::query(), $user)
                ->whereNotNull('dhaairaa')
                ->where('dhaairaa', '!=', '')
                ->distinct()
                ->orderBy('dhaairaa')
                ->pluck('dhaairaa')
                ->values()
            : collect();

        return Inertia::render('CallCenter/Index', [
            'voters' => $voters,
            'agents' => $agents,
            'voteStatuses' => $voteStatuses,
            'showDhaairaFilter' => $showDhaairaFilter,
            'dhaairas' => $dhaairas,
            'filters' => [
                'search' => $search,
                'cc_filter' => $ccFilter,
                'agent_filter' => $agentFilter,
                'vote_status_filter' => implode(',', $voteStatusFilter),
                'include_voted' => $includeVoted,
                'per_page' => (string) $perPage,
                'dhaaira_filter' => $dhaairaFilter,
            ],
        ]);
    }

    public function updateRemark(Request $request, VoterRecord $voter): RedirectResponse
    {
        $this->authorizeVoterAccess($request, $voter);

        $validated = $request->validate([
            'cc_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $voter->update(['cc_remarks' => $validated['cc_remarks'] ?? null]);

        return redirect()->route(
            'call-center.index',
            array_filter([
                'search' => $request->query('search'),
                'cc_filter' => $request->query('cc_filter'),
                'agent_filter' => $request->query('agent_filter'),
                'vote_status_filter' => $request->query('vote_status_filter'),
                'include_voted' => $request->query('include_voted'),
                'per_page' => $request->query('per_page'),
                'page' => $request->query('page'),
                'dhaaira_filter' => $request->query('dhaaira_filter'),
            ], static fn ($value) => $value !== null && $value !== '')
        );
    }

    private function authorizeVoterAccess(Request $request, VoterRecord $voter): void
    {
        $isAllowed = $this->applyCcScope(VoterRecord::query(), $request->user())
            ->whereKey($voter->getKey())
            ->exists();

        if (! $isAllowed) {
            abort(403);
        }
    }
}
