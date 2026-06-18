<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CancelReason;
use Illuminate\Http\Request;

class CancelReasonController extends Controller
{
    public function index()
    {
        return response()->json(CancelReason::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        return response()->json(CancelReason::create($data), 201);
    }

    public function destroy(CancelReason $cancelReason)
    {
        $cancelReason->delete();
        return response()->json(['message' => 'deleted']);
    }
}
