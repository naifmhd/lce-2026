<?php

namespace App\Http\Controllers;

use App\Concerns\AppliesVoterRoleScope;
use App\Http\Requests\CallCenterIndexRequest;
use App\Models\VoterRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CallCenterController extends Controller
{
    use AppliesVoterRoleScope;

    private const DEFAULT_PER_PAGE = 15;

    public function index(CallCenterIndexRequest $request): Response
    {
        $validated = $request->validated();
        $user = $request->user();
        $search = trim((string) ($validated['search'] ?? ''));
        $ccFilter = $validated['cc_filter'] ?? '';
        $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : self::DEFAULT_PER_PAGE;
        $page = max(1, (int) $request->query('page', 1));

        $terms = $search !== ''
            ? collect(explode(',', $search))->map(fn ($t) => trim($t))->filter()->all()
            : [];

        $voters = $this->applyVoterRoleScope(VoterRecord::query(), $user)
            ->where(fn ($q) => $q->whereNull('vote_status')->orWhere('vote_status', '!=', 'voted'))
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
            ->select([
                'id',
                'list_number',
                'id_card_number',
                'name',
                'address',
                'mobile',
                'registered_box',
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
                'cc_remarks' => $voter->cc_remarks,
                'photo_url' => $voter->photo_path !== null
                    ? Storage::disk('public')->url($voter->photo_path)
                    : null,
            ]);

        return Inertia::render('CallCenter/Index', [
            'voters' => $voters,
            'filters' => [
                'search' => $search,
                'cc_filter' => $ccFilter,
                'per_page' => (string) $perPage,
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
                'per_page' => $request->query('per_page'),
                'page' => $request->query('page'),
            ], static fn ($value) => $value !== null && $value !== '')
        );
    }

    private function authorizeVoterAccess(Request $request, VoterRecord $voter): void
    {
        $isAllowed = $this->applyVoterRoleScope(VoterRecord::query(), $request->user())
            ->whereKey($voter->getKey())
            ->exists();

        if (! $isAllowed) {
            abort(403);
        }
    }
}
