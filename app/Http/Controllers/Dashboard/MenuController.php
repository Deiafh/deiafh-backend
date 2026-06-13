<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuGroup;
use Illuminate\Http\Request;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;

class MenuController extends Controller
{
    public function store(Request $request, $groupId)
    {
        MenuGroup::findOrFail($groupId);
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:' . (5 * 1024 * 1024),
        ]);

        $manager = new ImageManager(new Driver());
        $path = 'storage/menus/' . uniqid() . '.webp';
        $manager->read($request->file('image'))
            ->scale(width: 1920)
            ->encode(new AutoEncoder('webp', quality: 85))
            ->save($path);

        $maxSort = Menu::where('menu_group_id', $groupId)->max('sort') ?? 0;

        $menu = Menu::create([
            'menu_group_id' => $groupId,
            'url'           => $path,
            'sort'          => $maxSort + 1,
            'active'        => ActiveStatus::Active->value,
        ]);

        $menu->url = url($menu->url);
        return response()->json($menu, 201);
    }

    public function update(Request $request, $groupId, $menuId)
    {
        $menu = Menu::where('id', $menuId)
            ->where('menu_group_id', $groupId)
            ->firstOrFail();

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:' . (5 * 1024 * 1024),
        ]);

        if ($menu->url && file_exists(base_path($menu->url))) {
            @unlink(base_path($menu->url));
        }

        $manager = new ImageManager(new Driver());
        $path = 'storage/menus/' . uniqid() . '.webp';
        $manager->read($request->file('image'))
            ->scale(width: 1920)
            ->encode(new AutoEncoder('webp', quality: 85))
            ->save($path);

        $menu->update(['url' => $path]);
        $menu->url = url($path);

        return response()->json($menu);
    }

    public function destroy($groupId, $menuId)
    {
        $menu = Menu::where('id', $menuId)
            ->where('menu_group_id', $groupId)
            ->firstOrFail();

        if ($menu->url && file_exists(base_path($menu->url))) {
            @unlink(base_path($menu->url));
        }

        $menu->delete();
        return response()->json(['message' => 'تم حذف الصورة بنجاح']);
    }
}
