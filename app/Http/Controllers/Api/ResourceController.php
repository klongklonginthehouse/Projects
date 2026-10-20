<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Report;
use App\Models\Resource;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    private const TYPES = ['lecture_notes', 'reviewer', 'past_exam', 'syllabus'];

    /** Search and browse verified material. */
    public function index(Request $request)
    {
        $uid = $request->user()->id;

        $query = Resource::query()
            ->with(['author:id,name', 'department:id,name'])
            ->withExists([
                'votes as voted' => fn ($q) => $q->where('user_id', $uid),
                'downloads as owned' => fn ($q) => $q->where('user_id', $uid),
            ])
            ->where('status', 'verified');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }
        if ($request->filled('type') && in_array($request->type, self::TYPES, true)) {
            $query->where('type', $request->type);
        }
        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->string('q'), '%_\\').'%';
            $query->where(fn ($q) => $q
                ->where('title', 'like', $term)
                ->orWhere('course_code', 'like', $term)
                ->orWhere('instructor', 'like', $term)
                ->orWhere('description', 'like', $term));
        }

        $request->input('sort') === 'new'
            ? $query->latest()
            : $query->orderByDesc('votes_count')->latest();

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, Resource $resource)
    {
        $this->assertVisible($request, $resource);
        $uid = $request->user()->id;

        $resource->load(['author:id,name', 'department:id,name'])
            ->loadExists([
                'votes as voted' => fn ($q) => $q->where('user_id', $uid),
                'downloads as owned' => fn ($q) => $q->where('user_id', $uid),
            ]);

        return response()->json($resource);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canUpload(), 403, 'Only authors, moderators and faculty can upload. Register as an author to contribute.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'department_id' => ['required', 'exists:departments,id'],
            'course_code' => ['required', 'string', 'max:20'],
            'instructor' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(self::TYPES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,txt,png,jpg,jpeg'],
        ]);

        // Only faculty can publish syllabi and official material.
        if ($data['type'] === 'syllabus' && $user->role !== 'faculty') {
            abort(403, 'Only faculty can publish official syllabi.');
        }

        $file = $request->file('file');
        $official = $user->role === 'faculty';

        $resource = Resource::create([
            ...collect($data)->except('file')->all(),
            'course_code' => strtoupper($data['course_code']),
            'user_id' => $user->id,
            'file_path' => $file->store('resources', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'status' => $official ? 'verified' : 'pending',
            'is_official' => $official,
            'verified_at' => $official ? now() : null,
        ]);

        return response()->json($resource->load(['author:id,name', 'department:id,name']), 201);
    }

    /** Toggle an upvote. */
    public function vote(Request $request, Resource $resource)
    {
        $this->assertVisible($request, $resource);
        abort_if($resource->user_id === $request->user()->id, 403, 'You cannot upvote your own upload.');

        return DB::transaction(function () use ($request, $resource) {
            $existing = Vote::where('user_id', $request->user()->id)->where('resource_id', $resource->id)->first();

            if ($existing) {
                $existing->delete();
                $resource->decrement('votes_count');
                $voted = false;
            } else {
                Vote::create(['user_id' => $request->user()->id, 'resource_id' => $resource->id]);
                $resource->increment('votes_count');
                $voted = true;
            }

            return response()->json(['voted' => $voted, 'votes_count' => $resource->fresh()->votes_count]);
        });
    }

    /** Spend credits once to unlock a file. Safe to call again: you are never charged twice. */
    public function unlock(Request $request, Resource $resource)
    {
        abort_unless($resource->status === 'verified', 404);

        return DB::transaction(function () use ($request, $resource) {
            $user = $request->user()->newQuery()->lockForUpdate()->find($request->user()->id);

            $already = Download::where('user_id', $user->id)->where('resource_id', $resource->id)->exists();
            $cost = ($resource->user_id === $user->id || $already) ? 0 : $resource->cost;

            if ($user->credits < $cost) {
                return response()->json([
                    'message' => "You need {$cost} credits and have {$user->credits}. Upload a note to earn more.",
                ], 402);
            }

            if (! $already) {
                if ($cost > 0) {
                    $user->decrement('credits', $cost);
                }
                Download::create(['user_id' => $user->id, 'resource_id' => $resource->id, 'credits_spent' => $cost]);
                $resource->increment('downloads_count');
            }

            return response()->json(['credits' => $user->fresh()->credits, 'spent' => $cost]);
        });
    }

    /** Stream the file. Needs an unlock record, authorship, or review rights. */
    public function file(Request $request, Resource $resource)
    {
        $user = $request->user();
        $allowed = $user->isStaff()
            || $resource->user_id === $user->id
            || ($resource->status === 'verified' && Download::where('user_id', $user->id)->where('resource_id', $resource->id)->exists());

        abort_unless($allowed, 403, 'Unlock this resource first.');
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404, 'No file is attached to this resource.');

        return Storage::disk('local')->download($resource->file_path, $resource->original_name);
    }

    public function report(Request $request, Resource $resource)
    {
        $this->assertVisible($request, $resource);

        Report::firstOrCreate(
            ['user_id' => $request->user()->id, 'resource_id' => $resource->id],
            ['reason' => $request->input('reason', 'copyright')],
        );

        return response()->json(['message' => 'Report sent to the moderators.'], 201);
    }

    private function assertVisible(Request $request, Resource $resource): void
    {
        $user = $request->user();
        abort_unless($resource->status === 'verified' || $resource->user_id === $user->id || $user->isStaff(), 404);
    }
}
