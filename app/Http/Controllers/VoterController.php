<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoterIndexRequest;
use App\Models\VoterRecord;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VoterController extends Controller
{
    public function index(VoterIndexRequest $request): Response
    {
        $validated = $request->validated();
        $search = trim((string) ($validated['search'] ?? ''));
        $dhaairaa = trim((string) ($validated['dhaairaa'] ?? ''));
        $majilisCon = trim((string) ($validated['majilis_con'] ?? ''));
        $selectedVoterId = isset($validated['selected']) ? (int) $validated['selected'] : null;

        $votersQuery = VoterRecord::query()
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

        $voters = $votersQuery
            ->orderBy('list_number')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (VoterRecord $voter) => [
                'id' => $voter->id,
                'list_number' => $voter->list_number,
                'id_card_number' => $voter->id_card_number,
                'name' => $voter->name,
                'mobile' => $voter->mobile,
                'address' => $voter->address,
                'dhaairaa' => $voter->dhaairaa,
                'majilis_con' => $voter->majilis_con,
                'vote_status' => $voter->vote_status,
                'photo_url' => $voter->photo_path !== null ? Storage::disk('public')->url($voter->photo_path) : null,
            ]);

        $selectedVoter = null;

        if ($selectedVoterId !== null) {
            $selectedVoterRecord = VoterRecord::query()->find($selectedVoterId);

            if ($selectedVoterRecord !== null) {
                $selectedVoter = [
                    'id' => $selectedVoterRecord->id,
                    'list_number' => $selectedVoterRecord->list_number,
                    'id_card_number' => $selectedVoterRecord->id_card_number,
                    'name' => $selectedVoterRecord->name,
                    'sex' => $selectedVoterRecord->sex,
                    'mobile' => $selectedVoterRecord->mobile,
                    'dob' => $selectedVoterRecord->dob?->format('Y-m-d'),
                    'age' => $selectedVoterRecord->age,
                    'island' => $selectedVoterRecord->island,
                    'majilis_con' => $selectedVoterRecord->majilis_con,
                    'address' => $selectedVoterRecord->address,
                    'dhaairaa' => $selectedVoterRecord->dhaairaa,
                    'mayor' => $selectedVoterRecord->mayor,
                    'raeesa' => $selectedVoterRecord->raeesa,
                    'council' => $selectedVoterRecord->council,
                    'wdc' => $selectedVoterRecord->wdc,
                    're_reg_travel' => $selectedVoterRecord->re_reg_travel,
                    'comments' => $selectedVoterRecord->comments,
                    'vote_status' => $selectedVoterRecord->vote_status,
                    'photo_url' => $selectedVoterRecord->photo_path !== null ? Storage::disk('public')->url($selectedVoterRecord->photo_path) : null,
                ];
            }
        }

        return Inertia::render('Voters/Index', [
            'voters' => $voters,
            'filters' => [
                'search' => $search,
                'dhaairaa' => $dhaairaa,
                'majilis_con' => $majilisCon,
                'selected' => $selectedVoterId,
            ],
            'filterOptions' => [
                'dhaairaa' => VoterRecord::query()
                    ->whereNotNull('dhaairaa')
                    ->where('dhaairaa', '!=', '')
                    ->distinct()
                    ->orderBy('dhaairaa')
                    ->pluck('dhaairaa')
                    ->values(),
                'majilis_con' => VoterRecord::query()
                    ->whereNotNull('majilis_con')
                    ->where('majilis_con', '!=', '')
                    ->distinct()
                    ->orderBy('majilis_con')
                    ->pluck('majilis_con')
                    ->values(),
            ],
            'selectedVoter' => $selectedVoter,
        ]);
    }
}
