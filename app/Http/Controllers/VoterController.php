<?php

namespace App\Http\Controllers;

use App\Concerns\AppliesVoterRoleScope;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Requests\VoterIndexRequest;
use App\Http\Requests\VoterUpdateRequest;
use App\Models\User;
use App\Models\VoterRecord;
use App\Services\RolePermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class VoterController extends Controller
{
    use AppliesVoterRoleScope;

    private const DEFAULT_PER_PAGE = 15;

    private const BLANK_PLEDGE_FILTER = '__BLANK__';

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
        $ageFrom = array_key_exists('age_from', $validated) ? (int) $validated['age_from'] : null;
        $ageTo = array_key_exists('age_to', $validated) ? (int) $validated['age_to'] : null;
        $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : self::DEFAULT_PER_PAGE;
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
                $ageFrom !== null,
                fn ($query) => $query->where('age', '>=', $ageFrom)
            )
            ->when(
                $ageTo !== null,
                fn ($query) => $query->where('age', '<=', $ageTo)
            )
            ->when(
                $councilPledge !== '',
                fn ($query) => $this->applyPledgeFilter($query, 'council', $councilPledge)
            )
            ->when(
                $wdcPledge !== '',
                fn ($query) => $this->applyPledgeFilter($query, 'wdc', $wdcPledge)
            )
            ->when(
                $mayorPledge !== '',
                fn ($query) => $this->applyPledgeFilter($query, 'mayor', $mayorPledge)
            )
            ->when(
                $raeesaPledge !== '',
                fn ($query) => $this->applyPledgeFilter($query, 'raeesa', $raeesaPledge)
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
            ->paginate($perPage, ['*'], 'page', $page)
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
                'comments' => $this->formatCommentsForDisplay($voter->comments),
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
                'age_from' => $ageFrom === null ? '' : (string) $ageFrom,
                'age_to' => $ageTo === null ? '' : (string) $ageTo,
                'per_page' => (string) $perPage,
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
            'pledgeVisibility' => $this->pledgeVisibility($user),
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
            'comments' => $this->appendCommentEntry(
                $voter->comments,
                $this->normalizeNullableText($validated['comments'] ?? null),
                $request->user(),
            ),
        ]);
        $svc = app(RolePermissionService::class);
        $pledgeUpdate = [];

        if ($svc->userHasPermission($request->user(), Permission::UpdateCouncilPledge)) {
            $pledgeUpdate['council'] = $validated['pledge']['council'] ?? null;
        }
        if ($svc->userHasPermission($request->user(), Permission::UpdateMayorPledge)) {
            $pledgeUpdate['mayor'] = $validated['pledge']['mayor'] ?? null;
        }
        if ($svc->userHasPermission($request->user(), Permission::UpdateRaeesaPledge)) {
            $pledgeUpdate['raeesa'] = $validated['pledge']['raeesa'] ?? null;
        }
        if ($svc->userHasPermission($request->user(), Permission::UpdateWdcPledge)) {
            $pledgeUpdate['wdc'] = $validated['pledge']['wdc'] ?? null;
        }

        if ($pledgeUpdate !== []) {
            $voter->pledge()->updateOrCreate(
                ['voter_id' => $voter->id],
                $pledgeUpdate
            );
        }

        $query = [
            'search' => $request->query('search'),
            'dhaairaa' => $request->query('dhaairaa'),
            'registered_box' => $request->query('registered_box'),
            'agent' => $request->query('agent'),
            'age_from' => $request->query('age_from'),
            'age_to' => $request->query('age_to'),
            'per_page' => $request->query('per_page'),
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

    public function export(VoterIndexRequest $request): HttpResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $search = trim((string) ($validated['search'] ?? ''));
        $dhaairaa = trim((string) ($validated['dhaairaa'] ?? ''));
        $registeredBox = trim((string) ($validated['registered_box'] ?? ''));
        $agent = trim((string) ($validated['agent'] ?? ''));
        $ageFrom = array_key_exists('age_from', $validated) ? (int) $validated['age_from'] : null;
        $ageTo = array_key_exists('age_to', $validated) ? (int) $validated['age_to'] : null;
        $councilPledge = $this->canFilterCouncilPledge($user) ? trim((string) ($validated['council_pledge'] ?? '')) : '';
        $wdcPledge = $this->canFilterWdcPledge($user) ? trim((string) ($validated['wdc_pledge'] ?? '')) : '';
        $mayorPledge = $this->canFilterMayorPledge($user) ? trim((string) ($validated['mayor_pledge'] ?? '')) : '';
        $raeesaPledge = $this->canFilterRaeesaPledge($user) ? trim((string) ($validated['raeesa_pledge'] ?? '')) : '';

        $pledgeVis = $this->pledgeVisibility($user);

        $voters = $this->applyVoterRoleScope(VoterRecord::query(), $user)
            ->when($search !== '', fn ($q) => $q->where(fn ($n) => $n
                ->where('id_card_number', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")))
            ->when($dhaairaa !== '', fn ($q) => $q->where('dhaairaa', $dhaairaa))
            ->when($registeredBox !== '', fn ($q) => $q->where('registered_box', $registeredBox))
            ->when($agent !== '', fn ($q) => $q->where(fn ($n) => collect(preg_split('/\s*\/\s*/', $agent) ?: [])
                ->each(fn ($a) => $n->orWhere('agent', 'like', "%{$a}%"))))
            ->when($ageFrom !== null, fn ($q) => $q->where('age', '>=', $ageFrom))
            ->when($ageTo !== null, fn ($q) => $q->where('age', '<=', $ageTo))
            ->when($councilPledge !== '', fn ($q) => $this->applyPledgeFilter($q, 'council', $councilPledge))
            ->when($wdcPledge !== '', fn ($q) => $this->applyPledgeFilter($q, 'wdc', $wdcPledge))
            ->when($mayorPledge !== '', fn ($q) => $this->applyPledgeFilter($q, 'mayor', $mayorPledge))
            ->when($raeesaPledge !== '', fn ($q) => $this->applyPledgeFilter($q, 'raeesa', $raeesaPledge))
            ->with(['pledge:voter_id,mayor,raeesa,council,wdc'])
            ->orderBy('list_number')
            ->get(['id', 'list_number', 'id_card_number', 'name', 'sex', 'age', 'dhaairaa', 'registered_box', 'mobile', 'address', 'vote_status', 'agent']);

        // Log the export activity
        activity()
            ->causedBy($user)
            ->withProperties([
                'filters' => array_filter([
                    'search' => $search ?: null,
                    'dhaairaa' => $dhaairaa ?: null,
                    'registered_box' => $registeredBox ?: null,
                    'agent' => $agent ?: null,
                    'age_from' => $ageFrom,
                    'age_to' => $ageTo,
                    'council_pledge' => $councilPledge ?: null,
                    'wdc_pledge' => $wdcPledge ?: null,
                    'mayor_pledge' => $mayorPledge ?: null,
                    'raeesa_pledge' => $raeesaPledge ?: null,
                ]),
                'total_records' => $voters->count(),
                'ip' => $request->ip(),
            ])
            ->log('Voter list exported');

        // Build CSV
        $headers = ['No.', 'ID Card', 'Name', 'Sex', 'Age', 'Dhaairaa', 'Box', 'Mobile', 'Address', 'Vote Status', 'Agent'];

        if ($pledgeVis['council']['view']) {
            $headers[] = 'Council Pledge';
        }
        if ($pledgeVis['wdc']['view']) {
            $headers[] = 'WDC Pledge';
        }
        if ($pledgeVis['mayor']['view']) {
            $headers[] = 'Mayor Pledge';
        }
        if ($pledgeVis['raeesa']['view']) {
            $headers[] = 'Raeesa Pledge';
        }

        $rows = $voters->map(function (VoterRecord $v) use ($pledgeVis): array {
            $row = [
                $v->list_number,
                $v->id_card_number,
                $v->name,
                $v->sex,
                $v->age,
                $v->dhaairaa,
                $v->registered_box,
                $v->mobile,
                $v->address,
                $v->vote_status,
                $v->agent,
            ];

            if ($pledgeVis['council']['view']) {
                $row[] = $v->pledge?->council;
            }
            if ($pledgeVis['wdc']['view']) {
                $row[] = $v->pledge?->wdc;
            }
            if ($pledgeVis['mayor']['view']) {
                $row[] = $v->pledge?->mayor;
            }
            if ($pledgeVis['raeesa']['view']) {
                $row[] = $v->pledge?->raeesa;
            }

            return $row;
        });

        $csv = collect([$headers])->merge($rows)->map(
            fn (array $row) => implode(',', array_map(
                fn ($cell) => '"'.str_replace('"', '""', (string) ($cell ?? '')).'"',
                $row,
            ))
        )->implode("\n");

        $filename = 'voters-'.now()->format('Y-m-d-His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
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

    private function appendCommentEntry(?string $existingComments, ?string $newComment, ?User $user): ?string
    {
        if ($newComment === null) {
            return $existingComments;
        }

        $commentEntries = $this->commentEntriesFromStorage($existingComments);
        $commentEntries[] = [
            'user_name' => $this->firstName($user?->name),
            'comment' => $newComment,
            'created_at' => now()->toIso8601String(),
        ];

        return json_encode($commentEntries, JSON_UNESCAPED_UNICODE) ?: $existingComments;
    }

    /**
     * @return array<int, array{user_name: string|null, comment: string, created_at: string|null}>
     */
    private function commentEntriesFromStorage(?string $storedComments): array
    {
        if ($storedComments === null || trim($storedComments) === '') {
            return [];
        }

        $decoded = json_decode($storedComments, true);

        if (! is_array($decoded)) {
            return [[
                'user_name' => null,
                'comment' => $storedComments,
                'created_at' => null,
            ]];
        }

        $entries = [];

        foreach ($decoded as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $comment = $entry['comment'] ?? null;

            if (! is_string($comment) || trim($comment) === '') {
                continue;
            }

            $entries[] = [
                'user_name' => is_string($entry['user_name'] ?? null) && trim((string) $entry['user_name']) !== ''
                    ? trim((string) $entry['user_name'])
                    : (is_numeric($entry['user_id'] ?? null) ? (string) ((int) $entry['user_id']) : null),
                'comment' => trim($comment),
                'created_at' => is_string($entry['created_at'] ?? null) ? $entry['created_at'] : null,
            ];
        }

        if ($entries !== []) {
            return $entries;
        }

        return [[
            'user_name' => null,
            'comment' => $storedComments,
            'created_at' => null,
        ]];
    }

    private function formatCommentsForDisplay(?string $storedComments): ?string
    {
        $commentEntries = $this->commentEntriesFromStorage($storedComments);

        if ($commentEntries === []) {
            return null;
        }

        $lines = array_map(
            fn (array $entry): string => $this->formatCommentLine($entry),
            $commentEntries,
        );

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param  array{user_name: string|null, comment: string, created_at: string|null}  $entry
     */
    private function formatCommentLine(array $entry): string
    {
        $prefixParts = [];

        if ($entry['user_name'] !== null && $entry['user_name'] !== '') {
            $prefixParts[] = $entry['user_name'];
        }

        if ($entry['created_at'] !== null) {
            try {
                $prefixParts[] = Carbon::parse($entry['created_at'])->format('Y-m-d H:i');
            } catch (Throwable) {
                $prefixParts[] = $entry['created_at'];
            }
        }

        if ($prefixParts === []) {
            return $entry['comment'];
        }

        return '['.implode(' | ', $prefixParts).'] '.$entry['comment'];
    }

    private function firstName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $trimmed = trim($name);

        if ($trimmed === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $trimmed) ?: [];

        return $parts[0] ?? null;
    }

    /**
     * @return array{council: array{view: bool, update: bool}, wdc: array{view: bool, update: bool}, mayor: array{view: bool, update: bool}, raeesa: array{view: bool, update: bool}}
     */
    private function pledgeVisibility(?User $user): array
    {
        $svc = app(RolePermissionService::class);

        return [
            'council' => [
                'view' => $svc->userHasPermission($user, Permission::ViewCouncilPledge),
                'update' => $svc->userHasPermission($user, Permission::UpdateCouncilPledge),
            ],
            'wdc' => [
                'view' => $svc->userHasPermission($user, Permission::ViewWdcPledge),
                'update' => $svc->userHasPermission($user, Permission::UpdateWdcPledge),
            ],
            'mayor' => [
                'view' => $svc->userHasPermission($user, Permission::ViewMayorPledge),
                'update' => $svc->userHasPermission($user, Permission::UpdateMayorPledge),
            ],
            'raeesa' => [
                'view' => $svc->userHasPermission($user, Permission::ViewRaeesaPledge),
                'update' => $svc->userHasPermission($user, Permission::UpdateRaeesaPledge),
            ],
        ];
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

    private function applyPledgeFilter(Builder $query, string $field, string $filterValue): void
    {
        if ($filterValue !== self::BLANK_PLEDGE_FILTER) {
            $query->whereHas('pledge', fn ($pledgeQuery) => $pledgeQuery->where($field, $filterValue));

            return;
        }

        $query->where(function ($nestedQuery) use ($field) {
            $nestedQuery
                ->whereDoesntHave('pledge')
                ->orWhereHas('pledge', function ($pledgeQuery) use ($field) {
                    $pledgeQuery
                        ->whereNull($field)
                        ->orWhere($field, '');
                });
        });
    }
}
