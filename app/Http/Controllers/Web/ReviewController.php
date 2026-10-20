<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index()
    {
        $items = Resource::with(['author:id,name', 'department:id,name'])
            ->withCount(['reports as open_reports' => fn ($q) => $q->whereNull('resolved_at')])
            ->where(fn ($q) => $q->where('status', 'pending')
                ->orWhereHas('reports', fn ($r) => $r->whereNull('resolved_at')))
            ->oldest()
            ->get();

        return view('review', ['items' => $items]);
    }

    public function approve(Request $request, Resource $resource)
    {
        if ($resource->user_id === $request->user()->id && $request->user()->role !== 'faculty') {
            return back()->with('error', 'Another reviewer must verify your own uploads.');
        }

        DB::transaction(function () use ($resource) {
            $wasPending = $resource->status === 'pending';
            $resource->update(['status' => 'verified', 'verified_at' => now()]);
            $resource->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);

            // Contributor reward: 5 credits the first time an upload is verified.
            if ($wasPending && ! $resource->is_official) {
                $resource->author()->increment('credits', 5);
            }
        });

        return back()->with('status', 'Marked as verified.');
    }

    public function reject(Resource $resource)
    {
        DB::transaction(function () use ($resource) {
            $resource->update(['status' => 'rejected']);
            $resource->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);
        });

        return back()->with('status', 'Rejected and removed from search.');
    }

    public function dismiss(Resource $resource)
    {
        $resource->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);

        return back()->with('status', 'Reports dismissed.');
    }
}
