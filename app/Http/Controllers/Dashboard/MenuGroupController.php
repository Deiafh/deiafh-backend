<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MenuGroup;
use Illuminate\Http\Request;

class MenuGroupController extends Controller
{
    public function index()
    {
        return MenuGroup::withCount(['branches', 'menus'])
            ->with('branches:id,title,menu_group_id')
            ->orderByDesc('created_at')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $group = MenuGroup::create($data);
        return response()->json(
            $group->loadCount(['branches', 'menus'])->load('branches:id,title,menu_group_id'),
            201
        );
    }

    public function update(Request $request, $id)
    {
        $group = MenuGroup::findOrFail($id);
        $data = $request->validate(['name' => 'required|string|max:255']);
        $group->update($data);
        return response()->json($group->loadCount(['branches', 'menus'])->load('branches:id,title,menu_group_id'));
    }

    public function destroy($id)
    {
        $group = MenuGroup::withCount('menus')->findOrFail($id);
        if ($group->menus_count > 0) {
            return response()->json(['message' => 'لا يمكن حذف المجموعة لأنها تحتوي على قوائم'], 422);
        }
        Branch::where('menu_group_id', $id)->update(['menu_group_id' => null]);
        $group->delete();
        return response()->json(['message' => 'تم حذف المجموعة بنجاح']);
    }

    public function assignBranches(Request $request, $id)
    {
        MenuGroup::findOrFail($id);
        $request->validate([
            'branch_ids'   => 'present|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ]);
        Branch::where('menu_group_id', $id)->update(['menu_group_id' => null]);
        if (count($request->branch_ids)) {
            Branch::whereIn('id', $request->branch_ids)->update(['menu_group_id' => $id]);
        }
        return response()->json(
            MenuGroup::withCount(['branches', 'menus'])->with('branches:id,title,menu_group_id')->findOrFail($id)
        );
    }

    public function menus($id)
    {
        $group = MenuGroup::findOrFail($id);
        return $group->menus()->get()->map(function ($menu) {
            $menu->url = url($menu->url);
            return $menu;
        });
    }

    public function updateSort(Request $request, $id)
    {
        MenuGroup::findOrFail($id);
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ]);
        foreach ($request->ids as $sort => $menuId) {
            \App\Models\Menu::where('id', $menuId)
                ->where('menu_group_id', $id)
                ->update(['sort' => $sort + 1]);
        }
        return response()->json(['message' => 'تم تحديث الترتيب']);
    }
}
