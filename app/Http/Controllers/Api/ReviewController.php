<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /** Uploads waiting for review plus anything with open copyright reports. */
    public function queue()
    {
        $items = Resource::with(['author:id,name', 'department:id,name',
                // Moderators need to see why an item was reported. The reason is
                // stored encrypted and is decrypted by the model cast here.
                'reports' => fn ($q) => $q->whereNull('resolved_at')
                    ->select('id', 'resource_id', 'reason', 'created_at'),
            ])
            ->withCount(['reports as open_reports' => fn ($q) => $q->whereNull('resolved_at')])
            ->where(fn ($q) => $q->where('status', 'pending')
                ->orWhereHas('reports', fn ($r) => $r->whereNull('resolved_at')))
            ->oldest()
            ->get();

        return response()->json($items);
    }

    public function approve(Request $request, Resource $resource)
    {
        abort_if(
            $resource->user_id === $request->user()->id && $request->user()->role !== 'faculty',
            403,
            'Another reviewer must verify your own uploads.'
        );

        DB::transaction(function () use ($resource) {
            $wasPending = $resource->status === 'pending';
            $resource->update(['status' => 'verified', 'verified_at' => now()]);
            $resource->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);

            // Contributor reward: 5 credits the first time an upload is verified.
            if ($wasPending && ! $resource->is_official) {
                $resource->author()->increment('credits', 5);
            }
        });

        return response()->json($resource->fresh());
    }

    public function reject(Resource $resource)
    {
        DB::transaction(function () use ($resource) {
            $resource->update(['status' => 'rejected']);
            $resource->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);
        });

        return response()->json($resource->fresh());
    }

    /** Dismiss reports and keep the resource as is. */
    public function dismissReports(Resource $resource)
    {
        $resource->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);

        return response()->json(['message' => 'Reports dismissed.']);
    }
}
