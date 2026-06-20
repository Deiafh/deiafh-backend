<?php

namespace App\Http\Controllers\Dashboard;

use app\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with('branches:id,title')
            ->orderBy('sort')
            ->get()
            ->map(fn($c) => array_merge($c->toArray(), [
                'branch_ids' => $c->branches->pluck('id'),
            ]));

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'active'     => 'in:active,inactive',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ]);

        $sort = (Category::max('sort') ?? 0) + 1;
        $category = Category::create([
            'title'  => $data['title'],
            'active' => $data['active'] ?? ActiveStatus::Active->value,
            'from'   => $data['from'] ?? null,
            'to'     => $data['to'] ?? null,
            'sort'   => $sort,
        ]);

        if (!empty($data['branch_ids'])) {
            $category->branches()->sync($data['branch_ids']);
        }

        return response()->json($category->load('branches:id,title'), 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'active'       => 'in:active,inactive',
            'from'         => 'nullable|date',
            'to'           => 'nullable|date',
            'branch_ids'   => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ]);

        $category->update([
            'title'  => $data['title'],
            'active' => $data['active'] ?? $category->active,
            'from'   => $data['from'] ?? null,
            'to'     => $data['to'] ?? null,
        ]);

        $category->branches()->sync($data['branch_ids'] ?? []);

        return response()->json($category->load('branches:id,title'));
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function updateSort(Request $request)
    {
        $request->validate([
            'sorts'   => 'required|array',
            'sorts.*' => 'integer|exists:categories,id',
        ]);

        DB::transaction(function () use ($request) {
            // Assign negative temps to avoid unique constraint collisions
            foreach ($request->sorts as $i => $id) {
                Category::where('id', $id)->update(['sort' => -($i + 1)]);
            }
            foreach ($request->sorts as $i => $id) {
                Category::where('id', $id)->update(['sort' => $i + 1]);
            }
        });

        return response()->json(['message' => 'sorted']);
    }
}
