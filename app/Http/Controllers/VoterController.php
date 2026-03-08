<?php

namespace App\Http\Controllers;

use App\Concerns\AppliesVoterRoleScope;
use App\Enums\UserRole;
use App\Http\Requests\VoterIndexRequest;
use App\Http\Requests\VoterUpdateRequest;
use App\Models\User;
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

    /**
     * @var array<int, string>
     */
    private const COUNCIL_PLEDGE_FILTER_ROLES = [
        UserRole::Dhaaira1Council->value,
        UserRole::Dhaaira2Council->value,
        UserRole::Dhaaira3Council->value,
        UserRole::Dhaaira4Council->value,
        UserRole::Dhaaira5Council->value,
        UserRole::Dhaaira6Council->value,
    ];

    /**
     * @var array<int, string>
     */
    private const WDC_PLEDGE_FILTER_ROLES = [
        UserRole::Dhaaira1Wdc->value,
        UserRole::Dhaaira2Wdc->value,
        UserRole::Dhaaira3Wdc->value,
        UserRole::Dhaaira4Wdc->value,
        UserRole::Dhaaira5Wdc->value,
        UserRole::Dhaaira6Wdc->value,
    ];

    /**
     * @var array<int, string>
     */
    private const MAYOR_PLEDGE_FILTER_ROLES = [
        UserRole::Mayor->value,
    ];

    /**
     * @var array<int, string>
     */
    private const RAEESA_PLEDGE_FILTER_ROLES = [
        UserRole::Raeesa->value,
    ];

    public function index(VoterIndexRequest $request): Response
    {
        $validated = $request->validated();
        $user = $request->user();
        $search = trim((string) ($validated['search'] ?? ''));
        $dhaairaa = trim((string) ($validated['dhaairaa'] ?? ''));
        $registeredBox = trim((string) ($validated['registered_box'] ?? ''));
        $agent = trim((string) ($validated['agent'] ?? ''));
        $councilPledge = trim((string) ($validated['council_pledge'] ?? ''));
        $wdcPledge = trim((string) ($validated['wdc_pledge'] ?? ''));
        $mayorPledge = trim((string) ($validated['mayor_pledge'] ?? ''));
        $raeesaPledge = trim((string) ($validated['raeesa_pledge'] ?? ''));

        $canFilterCouncilPledge = $this->canFilterCouncilPledge($user);
        $canFilterWdcPledge = $this->canFilterWdcPledge($user);
        $canFilterMayorPledge = $this->canFilterMayorPledge($user);
        $canFilterRaeesaPledge = $this->canFilterRaeesaPledge($user);

        $councilPledge = $canFilterCouncilPledge ? $councilPledge : '';
        $wdcPledge = $canFilterWdcPledge ? $wdcPledge : '';
        $mayorPledge = $canFilterMayorPledge ? $mayorPledge : '';
        $raeesaPledge = $canFilterRaeesaPledge ? $raeesaPledge : '';
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
                $registeredBox !== '',
                fn ($query) => $query->where('registered_box', $registeredBox)
            )
            ->when(
                $agent !== '',
                fn ($query) => $query->where(function ($nestedQuery) use ($agent) {
                    foreach (preg_split('/\s*\/\s*/', $agent) ?: [] as $singleAgent) {
                        $nestedQuery->orWhere('agent', 'like', "%{$singleAgent}%");
                    }
                })
            )
            ->when(
                $councilPledge !== '',
                fn ($query) => $query->whereHas('pledge', fn ($pledgeQuery) => $pledgeQuery->where('council', $councilPledge))
            )
            ->when(
                $wdcPledge !== '',
                fn ($query) => $query->whereHas('pledge', fn ($pledgeQuery) => $pledgeQuery->where('wdc', $wdcPledge))
            )
            ->when(
                $mayorPledge !== '',
                fn ($query) => $query->whereHas('pledge', fn ($pledgeQuery) => $pledgeQuery->where('mayor', $mayorPledge))
            )
            ->when(
                $raeesaPledge !== '',
                fn ($query) => $query->whereHas('pledge', fn ($pledgeQuery) => $pledgeQuery->where('raeesa', $raeesaPledge))
            );

        $voters = (clone $votersQuery)
            ->select([
                'id',
                'list_number',
                'id_card_number',
                'agent',
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
                'agent' => $voter->agent,
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

        return Inertia::render('Voters/Index', [
            'voters' => $voters,
            'filters' => [
                'search' => $search,
                'dhaairaa' => $dhaairaa,
                'registered_box' => $registeredBox,
                'agent' => $agent,
                'council_pledge' => $councilPledge,
                'wdc_pledge' => $wdcPledge,
                'mayor_pledge' => $mayorPledge,
                'raeesa_pledge' => $raeesaPledge,
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
                'registered_box' => Cache::remember('voters:filter-options:registered_box:'.md5(json_encode([
                    'user' => $user?->id,
                    'roles' => $user?->roleKeys() ?? [],
                ])), now()->addMinutes(15), function () use ($user) {
                    return $this->applyVoterRoleScope(VoterRecord::query(), $user)
                        ->whereNotNull('registered_box')
                        ->where('registered_box', '!=', '')
                        ->distinct()
                        ->orderBy('registered_box')
                        ->pluck('registered_box')
                        ->values();
                }),
                'agent' => $this->applyVoterRoleScope(VoterRecord::query(), $user)
                    ->whereNotNull('agent')
                    ->where('agent', '!=', '')
                    ->pluck('agent')
                    ->flatMap(static function (string $agent): array {
                        return preg_split('/\s*\/\s*/', trim($agent)) ?: [];
                    })
                    ->map(static fn (string $agent): string => trim($agent))
                    ->filter(static fn (string $agent): bool => $agent !== '')
                    ->unique()
                    ->sort()
                    ->values(),
            ],
            'selectedVoter' => null,
            'pledgeOptions' => self::PLEDGE_OPTIONS,
            'pledgeFilterVisibility' => [
                'council' => $canFilterCouncilPledge,
                'wdc' => $canFilterWdcPledge,
                'mayor' => $canFilterMayorPledge,
                'raeesa' => $canFilterRaeesaPledge,
            ],
        ]);
    }

    public function update(VoterUpdateRequest $request, VoterRecord $voter): RedirectResponse
    {
        $this->authorizeVoterAccess($request, $voter);

        $validated = $request->validated();

        $voter->update([
            'agent' => $this->normalizeNullableText($validated['agent'] ?? null),
            'registered_box' => $this->normalizeNullableText($validated['registered_box'] ?? null),
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
            'registered_box' => $request->query('registered_box'),
            'agent' => $request->query('agent'),
            'council_pledge' => $request->query('council_pledge'),
            'wdc_pledge' => $request->query('wdc_pledge'),
            'mayor_pledge' => $request->query('mayor_pledge'),
            'raeesa_pledge' => $request->query('raeesa_pledge'),
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

    private function canFilterCouncilPledge(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(self::COUNCIL_PLEDGE_FILTER_ROLES);
    }

    private function canFilterWdcPledge(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(self::WDC_PLEDGE_FILTER_ROLES);
    }

    private function canFilterMayorPledge(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(self::MAYOR_PLEDGE_FILTER_ROLES);
    }

    private function canFilterRaeesaPledge(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(self::RAEESA_PLEDGE_FILTER_ROLES);
    }
}
