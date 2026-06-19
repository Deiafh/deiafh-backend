<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller
{
    /**
     * Heartbeat from the dashboard: refresh the user's presence + current page.
     * Uses a query-builder update so it fires no model events and skips updated_at.
     */
    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'page' => 'nullable|string|max:255',
        ]);

        User::where('id', Auth::guard('api')->id())->update([
            'last_seen_at' => now(),
            'current_page' => $data['page'] ?? null,
        ]);

        return response()->json(['status' => true]);
    }
}
