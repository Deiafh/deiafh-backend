<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\NumberType;
use App\Http\Controllers\Controller;
use App\Models\Number;
use Illuminate\Http\Request;

class NumberController extends Controller
{
    public function index()
    {
        return Number::orderBy('type')->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string|max:30',
            'type'   => 'required|in:' . implode(',', NumberType::getList()),
        ]);

        $number = Number::create($request->only('number', 'type'));
        return response()->json($number, 201);
    }

    public function destroy($id)
    {
        Number::findOrFail($id)->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }
}
