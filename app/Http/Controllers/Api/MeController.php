<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public static function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'credits' => $user->credits,
            'badge' => $user->badge(),
            'reputation' => $user->reputation(),
        ];
    }

    public function show(Request $request)
    {
        return response()->json(self::payload($request->user()->fresh()));
    }

    /** Things the user uploaded or has already downloaded. */
    public function shelf(Request $request)
    {
        $user = $request->user();

        $items = Resource::with(['author:id,name', 'department:id,name'])
            ->where(fn ($q) => $q->where('user_id', $user->id)
                ->orWhereHas('downloads', fn ($d) => $d->where('user_id', $user->id)))
            ->latest()
            ->get();

        return response()->json($items);
    }

    public function departments()
    {
        return response()->json(Department::orderBy('name')->get());
    }
}
