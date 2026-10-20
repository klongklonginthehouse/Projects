<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function shelf(Request $request)
    {
        $user = $request->user();

        $items = Resource::with(['department:id,name'])
            ->where(fn ($q) => $q->where('user_id', $user->id)
                ->orWhereHas('downloads', fn ($d) => $d->where('user_id', $user->id)))
            ->latest()
            ->get();

        return view('shelf', ['items' => $items, 'badge' => $user->badge(), 'reputation' => $user->reputation()]);
    }
}
