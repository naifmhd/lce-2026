<?php

namespace App\Http\Controllers;

use App\Models\Pledge;
use App\Models\VoterRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::with(['causer'])
            ->whereIn('log_name', ['voter', 'pledge', 'default'])
            ->latest();

        if ($request->filled('causer')) {
            $query->where('causer_id', $request->input('causer'));
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        $logs = $query->paginate(50)->withQueryString();

        // Resolve voter info for each log entry
        $subjectIds = [
            'voter' => [],
            'pledge' => [],
        ];

        foreach ($logs as $log) {
            if ($log->subject_type === (new VoterRecord)->getMorphClass() || $log->subject_type === VoterRecord::class) {
                $subjectIds['voter'][] = $log->subject_id;
            } elseif ($log->subject_type === (new Pledge)->getMorphClass() || $log->subject_type === Pledge::class) {
                $subjectIds['pledge'][] = $log->subject_id;
            }
        }

        $voters = VoterRecord::whereIn('id', array_unique($subjectIds['voter']))
            ->get(['id', 'list_number', 'name'])
            ->keyBy('id');

        $pledges = Pledge::whereIn('id', array_unique($subjectIds['pledge']))
            ->get(['id', 'voter_id'])
            ->keyBy('id');

        $pledgeVoters = VoterRecord::whereIn('id', $pledges->pluck('voter_id')->unique()->toArray())
            ->get(['id', 'list_number', 'name'])
            ->keyBy('id');

        $mapped = $logs->through(function (Activity $log) use ($voters, $pledges, $pledgeVoters): array {
            $voter = null;

            if ($log->subject_type === VoterRecord::class || str_ends_with((string) $log->subject_type, 'VoterRecord')) {
                $v = $voters->get($log->subject_id);
                $voter = $v ? ['list_number' => $v->list_number, 'name' => $v->name] : null;
            } elseif ($log->subject_type === Pledge::class || str_ends_with((string) $log->subject_type, 'Pledge')) {
                $pledge = $pledges->get($log->subject_id);
                if ($pledge) {
                    $v = $pledgeVoters->get($pledge->voter_id);
                    $voter = $v ? ['list_number' => $v->list_number, 'name' => $v->name] : null;
                }
            }

            $props = $log->properties->toArray();

            return [
                'id' => $log->id,
                'log_name' => $log->log_name,
                'description' => $log->description,
                'event' => $log->event,
                'voter' => $voter,
                'causer' => $log->causer ? ['id' => $log->causer->id, 'name' => $log->causer->name] : null,
                'properties' => $props,
                'created_at' => $log->created_at?->toIso8601String(),
            ];
        });

        $causers = Activity::whereIn('log_name', ['voter', 'pledge', 'default'])
            ->with('causer')
            ->select('causer_id', 'causer_type')
            ->distinct()
            ->get()
            ->map(fn (Activity $a) => $a->causer ? ['id' => $a->causer->id, 'name' => $a->causer->name] : null)
            ->filter()
            ->unique('id')
            ->values();

        return Inertia::render('ActivityLog/Index', [
            'logs' => $mapped,
            'causers' => $causers,
            'filters' => $request->only(['causer', 'log_name', 'event']),
        ]);
    }
}
