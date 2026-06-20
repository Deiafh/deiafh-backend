<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Label;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index()
    {
        return Label::orderBy('name')->get(['id', 'name', 'emoji', 'color']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'emoji' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        return Label::create($data);
    }

    public function update(Request $request, Label $label)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'emoji' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $label->update($data);
        return $label->only(['id', 'name', 'emoji', 'color']);
    }

    public function destroy(Label $label)
    {
        $label->delete();
        return response()->json(['message' => 'deleted']);
    }
}
