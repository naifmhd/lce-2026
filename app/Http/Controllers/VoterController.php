<?php

namespace App\Http\Controllers;

use App\Concerns\AppliesVoterRoleScope;
use App\Http\Requests\VoterIndexRequest;
use App\Http\Requests\VoterUpdateRequest;
use App\Models\VoterRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VoterController extends Controller
{
    use AppliesVoterRoleScope;

    /**
     * @var array<int, string>
     */
    private const PLEDGE_OPTIONS = ['PNC', 'MDP', 'UN', 'NOT VOTING'];

    public function index(VoterIndexRequest $request): Response
    {
        $validated = $request->validated();
        $user = $request->user();
        $search = trim((string) ($validated['search'] ?? ''));
        $dhaairaa = trim((string) ($validated['dhaairaa'] ?? ''));
        $majilisCon = trim((string) ($validated['majilis_con'] ?? ''));
        $page = max(1, (int) $request->query('page', 1));

        $votersQuery = $this->applyVoterRoleScope(VoterRecord::query(), $user)
            ->when(
                $search !== '',
                fn ($query) => $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('id_card_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                })
            )
            ->when(
                $dhaairaa !== '',
                fn ($query) => $query->where('dhaairaa', $dhaairaa)
            )
            ->when(
                $majilisCon !== '',
                fn ($query) => $query->where('majilis_con', $majilisCon)
            );

        $cacheKey = 'voters:list:'.md5(json_encode([
            'user' => $user?->id,
            'roles' => $user?->roleKeys() ?? [],
            'search' => $search,
            'dhaairaa' => $dhaairaa,
            'majilis_con' => $majilisCon,
            'page' => $page,
        ]));

        $voters = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($votersQuery, $page) {
            return (clone $votersQuery)
                ->select([
                    'id',
                    'list_number',
                    'id_card_number',
                    'name',
                    'sex',
                    'mobile',
                    'dob',
                    'age',
                    'registered_box',
                    'address',
                    'dhaairaa',
                    'majilis_con',
                    're_reg_travel',
                    'comments',
                    'vote_status',
                    'photo_path',
                ])
                ->with(['pledge:voter_id,mayor,raeesa,council,wdc'])
                ->orderBy('list_number')
                ->simplePaginate(15, ['*'], 'page', $page)
                ->withQueryString()
                ->through(fn (VoterRecord $voter) => [
                    'id' => $voter->id,
                    'list_number' => $voter->list_number,
                    'id_card_number' => $voter->id_card_number,
                    'name' => $voter->name,
                    'sex' => $voter->sex,
                    'mobile' => $voter->mobile,
                    'dob' => $voter->dob?->format('Y-m-d'),
                    'age' => $voter->age,
                    'registered_box' => $voter->registered_box,
                    'address' => $voter->address,
                    'dhaairaa' => $voter->dhaairaa,
                    'majilis_con' => $voter->majilis_con,
                    're_reg_travel' => $voter->re_reg_travel,
                    'comments' => $voter->comments,
                    'vote_status' => $voter->vote_status,
                    'pledge' => [
                        'mayor' => $voter->pledge?->mayor,
                        'raeesa' => $voter->pledge?->raeesa,
                        'council' => $voter->pledge?->council,
                        'wdc' => $voter->pledge?->wdc,
                    ],
                    'photo_url' => $voter->photo_path !== null ? Storage::disk('public')->url($voter->photo_path) : null,
                ]);
        });

        return Inertia::render('Voters/Index', [
            'voters' => $voters,
            'filters' => [
                'search' => $search,
                'dhaairaa' => $dhaairaa,
                'majilis_con' => $majilisCon,
            ],
            'filterOptions' => [
                'dhaairaa' => Cache::remember('voters:filter-options:dhaairaa:'.md5(json_encode([
                    'user' => $user?->id,
                    'roles' => $user?->roleKeys() ?? [],
                ])), now()->addMinutes(15), function () use ($user) {
                    return $this->applyVoterRoleScope(VoterRecord::query(), $user)
                        ->whereNotNull('dhaairaa')
                        ->where('dhaairaa', '!=', '')
                        ->distinct()
                        ->orderBy('dhaairaa')
                        ->pluck('dhaairaa')
                        ->values();
                }),
                'majilis_con' => Cache::remember('voters:filter-options:majilis_con:'.md5(json_encode([
                    'user' => $user?->id,
                    'roles' => $user?->roleKeys() ?? [],
                ])), now()->addMinutes(15), function () use ($user) {
                    return $this->applyVoterRoleScope(VoterRecord::query(), $user)
                        ->whereNotNull('majilis_con')
                        ->where('majilis_con', '!=', '')
                        ->distinct()
                        ->orderBy('majilis_con')
                        ->pluck('majilis_con')
                        ->values();
                }),
            ],
            'selectedVoter' => null,
            'pledgeOptions' => self::PLEDGE_OPTIONS,
        ]);
    }

    public function update(VoterUpdateRequest $request, VoterRecord $voter): RedirectResponse
    {
        $this->authorizeVoterAccess($request, $voter);

        $validated = $request->validated();

        $voter->update([
            'mobile' => $this->normalizeNullableText($validated['mobile'] ?? null),
            're_reg_travel' => $this->normalizeNullableText($validated['re_reg_travel'] ?? null),
            'comments' => $this->normalizeNullableText($validated['comments'] ?? null),
        ]);

        $voter->pledge()->updateOrCreate(
            ['voter_id' => $voter->id],
            [
                'mayor' => $validated['pledge']['mayor'] ?? null,
                'raeesa' => $validated['pledge']['raeesa'] ?? null,
                'council' => $validated['pledge']['council'] ?? null,
                'wdc' => $validated['pledge']['wdc'] ?? null,
            ]
        );

        $query = [
            'search' => $request->query('search'),
            'dhaairaa' => $request->query('dhaairaa'),
            'majilis_con' => $request->query('majilis_con'),
            'page' => $request->query('page'),
        ];

        return redirect()->route(
            'voters.index',
            array_filter($query, static fn ($value) => $value !== null && $value !== '')
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

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
