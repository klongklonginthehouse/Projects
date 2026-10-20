<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Download;
use App\Models\Report;
use App\Models\Resource;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    private const TYPES = ['lecture_notes', 'reviewer', 'past_exam', 'syllabus'];

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

        return view('browse', [
            'items' => $query->paginate(10)->withQueryString(),
            'departments' => Department::orderBy('name')->get(),
            'types' => self::TYPES,
        ]);
    }

    public function show(Request $request, Resource $resource)
    {
        $this->assertVisible($request, $resource);
        $uid = $request->user()->id;

        $resource->load(['author:id,name', 'department:id,name'])->loadExists([
            'votes as voted' => fn ($q) => $q->where('user_id', $uid),
            'downloads as owned' => fn ($q) => $q->where('user_id', $uid),
        ]);

        return view('show', ['r' => $resource]);
    }

    public function create(Request $request)
    {
        if (! $request->user()->canUpload()) {
            return redirect()->route('browse')->with('error', 'Only authors, moderators and faculty can upload. Register an author account to contribute.');
        }

        return view('upload', ['departments' => Department::orderBy('name')->get(), 'types' => self::TYPES]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canUpload(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'department_id' => ['required', 'exists:departments,id'],
            'course_code' => ['required', 'string', 'max:20'],
            'instructor' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(self::TYPES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,txt,png,jpg,jpeg'],
        ]);

        if ($data['type'] === 'syllabus' && $user->role !== 'faculty') {
            return back()->withInput()->withErrors(['type' => 'Only faculty can publish official syllabi.']);
        }

        $file = $request->file('file');
        $official = $user->role === 'faculty';

        Resource::create([
            ...collect($data)->except('file')->all(),
            'course_code' => strtoupper($data['course_code']),
            'user_id' => $user->id,
            'file_path' => $file->store('resources', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'status' => $official ? 'verified' : 'pending',
            'is_official' => $official,
            'verified_at' => $official ? now() : null,
        ]);

        return redirect()->route($official ? 'browse' : 'shelf')->with(
            'status',
            $official ? 'Published as official material.' : 'Uploaded. Moderators will review it, and you earn 5 credits once it is verified.'
        );
    }

    public function vote(Request $request, Resource $resource)
    {
        $this->assertVisible($request, $resource);

        if ($resource->user_id === $request->user()->id) {
            return back()->with('error', 'You cannot upvote your own upload.');
        }

        DB::transaction(function () use ($request, $resource) {
            $existing = Vote::where('user_id', $request->user()->id)->where('resource_id', $resource->id)->first();

            if ($existing) {
                $existing->delete();
                $resource->decrement('votes_count');
            } else {
                Vote::create(['user_id' => $request->user()->id, 'resource_id' => $resource->id]);
                $resource->increment('votes_count');
            }
        });

        return back();
    }

    /** Spends credits once (never twice), then starts the file download. */
    public function download(Request $request, Resource $resource)
    {
        abort_unless($resource->status === 'verified', 404);

        if (! $resource->file_path || ! Storage::disk('local')->exists($resource->file_path)) {
            return back()->with('error', 'No file is attached to this resource yet.');
        }

        $result = DB::transaction(function () use ($request, $resource) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->first();

            $already = Download::where('user_id', $user->id)->where('resource_id', $resource->id)->exists();
            $cost = ($resource->user_id === $user->id || $already) ? 0 : $resource->cost;

            if ($user->credits < $cost) {
                return ['error' => "You need {$cost} credits and have {$user->credits}. Upload a note to earn more."];
            }

            if (! $already) {
                if ($cost > 0) {
                    $user->decrement('credits', $cost);
                }
                Download::create(['user_id' => $user->id, 'resource_id' => $resource->id, 'credits_spent' => $cost]);
                $resource->increment('downloads_count');
            }

            return ['spent' => $cost];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        // Redirect first so the credits counter refreshes; the layout then starts the download.
        return back()
            ->with('status', $result['spent'] > 0 ? "Download started. {$result['spent']} credits spent." : 'Download started.')
            ->with('download_url', route('resources.file', $resource));
    }

    /** Direct file access for the author and reviewers (e.g. to check a pending upload). */
    public function file(Request $request, Resource $resource)
    {
        $user = $request->user();
        $allowed = $user->isStaff() || $resource->user_id === $user->id
            || ($resource->status === 'verified' && Download::where('user_id', $user->id)->where('resource_id', $resource->id)->exists());

        abort_unless($allowed, 403);
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download($resource->file_path, $resource->original_name);
    }

    public function report(Request $request, Resource $resource)
    {
        $this->assertVisible($request, $resource);

        Report::firstOrCreate(
            ['user_id' => $request->user()->id, 'resource_id' => $resource->id],
            ['reason' => 'copyright'],
        );

        return back()->with('status', 'Report sent to the moderators.');
    }

    private function assertVisible(Request $request, Resource $resource): void
    {
        $user = $request->user();
        abort_unless($resource->status === 'verified' || $resource->user_id === $user->id || $user->isStaff(), 404);
    }
}
