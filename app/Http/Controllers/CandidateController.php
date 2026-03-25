<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateRequest;
use App\Models\Candidate;
use App\Models\ElectionRace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function index(): Response
    {
        $candidates = Candidate::query()
            ->with('race:id,name,sort_order')
            ->orderBy(
                ElectionRace::select('sort_order')->whereColumn('election_races.id', 'candidates.race_id'),
            )
            ->orderBy('affiliation')
            ->orderBy('name')
            ->get()
            ->map(fn (Candidate $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'candidate_number' => $candidate->candidate_number,
                'affiliation' => $candidate->affiliation,
                'address' => $candidate->address,
                'race_id' => $candidate->race_id,
                'race_name' => $candidate->race->name,
            ]);

        $races = ElectionRace::query()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'dhaaira', 'type']);

        return Inertia::render('Candidates/Index', [
            'candidates' => $candidates,
            'races' => $races,
        ]);
    }

    public function store(CandidateRequest $request): RedirectResponse
    {
        Candidate::create($request->validated());

        return redirect()->route('candidates.index');
    }

    public function update(CandidateRequest $request, Candidate $candidate): RedirectResponse
    {
        $candidate->update($request->validated());

        return redirect()->route('candidates.index');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $candidate->delete();

        return redirect()->route('candidates.index');
    }
}
