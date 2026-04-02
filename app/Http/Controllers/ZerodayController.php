<?php

namespace App\Http\Controllers;

use App\Concerns\AppliesVoterRoleScope;
use App\Http\Requests\ZerodayIndexRequest;
use App\Models\VoterRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ZerodayController extends Controller
{
    use AppliesVoterRoleScope;

    private const DEFAULT_PER_PAGE = 15;

    public function index(ZerodayIndexRequest $request): Response
    {
        $validated = $request->validated();
        $user = $request->user();
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : self::DEFAULT_PER_PAGE;
        $includeVoted = filter_var($validated['include_voted'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $page = max(1, (int) $request->query('page', 1));

        $voters = null;

        if ($search !== '') {
            $terms = collect(explode(',', $search))->map(fn ($t) => trim($t))->filter()->all();

            $voters = $this->applyVoterRoleScope(VoterRecord::query(), $user)
                ->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->where(function ($q) use ($term) {
                            $q->where('id_card_number', 'like', "%{$term}%")
                                ->orWhere('name', 'like', "%{$term}%")
                                ->orWhere('address', 'like', "%{$term}%")
                                ->orWhere('mobile', 'like', "%{$term}%");
                        });
                    }
                })
                ->when(
                    ! $includeVoted,
                    fn ($query) => $query->where(fn ($q) => $q->whereNull('vote_status')->orWhere('vote_status', '!=', 'voted'))
                )
                ->select([
                    'id',
                    'list_number',
                    'id_card_number',
                    'name',
                    'address',
                    'mobile',
                    'registered_box',
                    'vote_status',
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
                    'vote_status' => $voter->vote_status,
                    'photo_url' => $voter->photo_path !== null
                        ? Storage::disk('public')->url($voter->photo_path)
                        : null,
                ]);
        }

        $remainingCount = $this->applyVoterRoleScope(VoterRecord::query(), $user)
            ->where(fn ($q) => $q->whereNull('vote_status')->orWhere('vote_status', '!=', 'voted'))
            ->count();

        return Inertia::render('Zeroday/Index', [
            'voters' => $voters,
            'remainingCount' => $remainingCount,
            'filters' => [
                'search' => $search,
                'per_page' => (string) $perPage,
                'include_voted' => $includeVoted,
            ],
        ]);
    }

    public function markVoted(Request $request, VoterRecord $voter): RedirectResponse
    {
        $this->authorizeVoterAccess($request, $voter);

        $voter->update(['vote_status' => 'voted']);

        return redirect()->route(
            'zeroday.index',
            array_filter([
                'search' => $request->query('search'),
                'per_page' => $request->query('per_page'),
                'include_voted' => $request->query('include_voted'),
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
