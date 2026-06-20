<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\WorkingPeriodGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $size      = $request->size ?? 10;
        $orderBy   = in_array($request->orderBy, ['id', 'title', 'tax', 'active']) ? $request->orderBy : 'id';
        $orderDir  = $request->orderDir === 'desc' ? 'desc' : 'asc';

        $branches = Branch::select(['id', 'title', 'address', 'google_map_url', 'tax', 'active', 'working_period_group_id', 'is_delivery_available', 'is_pickup_available', 'is_busy', 'order_time_from', 'order_time_to'])
            ->when($request->title, fn($q) => $q->where('title', 'like', '%' . $request->title . '%'))
            ->when($request->filled('active'), fn($q) => $q->where('active', $request->active))
            ->orderBy($orderBy, $orderDir)
            ->paginate($size);

        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'                 => 'required|string|max:255',
            'address'               => 'nullable|string|max:500',
            'google_map_url'        => 'nullable|string|max:1000',
            'tax'                   => 'required|numeric|min:0|max:100',
            'active'                => 'sometimes|in:active,inactive',
            'is_delivery_available' => 'sometimes|boolean',
            'is_pickup_available'   => 'sometimes|boolean',
            'is_busy'               => 'sometimes|boolean',
            'order_time_from'       => 'nullable|integer|min:1',
            'order_time_to'         => 'nullable|integer|min:1',
        ]);

        $branch = Branch::create([
            'title'                 => $request->title,
            'address'               => $request->address,
            'google_map_url'        => $request->google_map_url,
            'tax'                   => $request->tax,
            'active'                => $request->active ?? ActiveStatus::Active->value,
            'is_delivery_available' => $request->boolean('is_delivery_available', true),
            'is_pickup_available'   => $request->boolean('is_pickup_available', true),
            'is_busy'               => $request->boolean('is_busy', false),
            'order_time_from'       => $request->order_time_from,
            'order_time_to'         => $request->order_time_to,
        ]);

        return response()->json($branch->only(['id', 'title', 'address', 'google_map_url', 'tax', 'active', 'working_period_group_id', 'is_delivery_available', 'is_pickup_available', 'is_busy', 'order_time_from', 'order_time_to']), 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'                 => 'required|string|max:255',
            'address'               => 'nullable|string|max:500',
            'google_map_url'        => 'nullable|string|max:1000',
            'tax'                   => 'required|numeric|min:0|max:100',
            'active'                => 'sometimes|in:active,inactive',
            'is_delivery_available' => 'sometimes|boolean',
            'is_pickup_available'   => 'sometimes|boolean',
            'is_busy'               => 'sometimes|boolean',
            'order_time_from'       => 'nullable|integer|min:1',
            'order_time_to'         => 'nullable|integer|min:1',
        ]);

        $branch    = Branch::findOrFail($id);
        $newActive = $request->active ?? $branch->active;

        if ($newActive === ActiveStatus::Inactive->value && $branch->active === ActiveStatus::Active->value) {
            if (Branch::where('active', ActiveStatus::Active->value)->count() <= 1) {
                return response()->json(['message' => 'يجب أن يكون هناك فرع نشط واحد على الأقل'], 422);
            }
        }

        $delivery = $request->boolean('is_delivery_available', $branch->is_delivery_available);
        $pickup   = $request->boolean('is_pickup_available', $branch->is_pickup_available);

        if (!$delivery && !$pickup) {
            return response()->json(['message' => 'يجب تفعيل التوصيل أو الاستلام على الأقل'], 422);
        }

        $branch->update([
            'title'                 => $request->title,
            'address'               => $request->address,
            'google_map_url'        => $request->google_map_url,
            'tax'                   => $request->tax,
            'active'                => $newActive,
            'is_delivery_available' => $delivery,
            'is_pickup_available'   => $pickup,
            'is_busy'               => $request->boolean('is_busy', $branch->is_busy),
            'order_time_from'       => $request->order_time_from,
            'order_time_to'         => $request->order_time_to,
        ]);

        return response()->json($branch->only(['id', 'title', 'address', 'google_map_url', 'tax', 'active', 'working_period_group_id', 'is_delivery_available', 'is_pickup_available', 'is_busy', 'order_time_from', 'order_time_to']));
    }

    public function destroy($id)
    {
        if (Branch::count() <= 1) {
            return response()->json(['message' => 'لا يمكن حذف الفرع الوحيد'], 422);
        }

        $branch = Branch::findOrFail($id);

        // MariaDB cascade bug workaround: disable FK checks for the delete
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $branch->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json(['message' => 'تم حذف الفرع بنجاح']);
    }

    public function toggleActive($id)
    {
        $branch = Branch::findOrFail($id);

        if ($branch->active === ActiveStatus::Active->value) {
            if (Branch::where('active', ActiveStatus::Active->value)->count() <= 1) {
                return response()->json(['message' => 'يجب أن يكون هناك فرع نشط واحد على الأقل'], 422);
            }
            $branch->update(['active' => ActiveStatus::Inactive->value]);
        } else {
            $branch->update(['active' => ActiveStatus::Active->value]);
        }

        return response()->json(['active' => $branch->active]);
    }

    public function toggleBusy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->update(['is_busy' => !$branch->is_busy]);

        return response()->json(['is_busy' => $branch->is_busy]);
    }

    public function assign(Request $request, $branchId)
    {
        $request->validate(['groupId' => 'required|integer|exists:working_period_groups,id']);

        Branch::findOrFail($branchId)->update(['working_period_group_id' => $request->groupId]);

        return response()->json(['message' => 'Branch assigned successfully']);
    }

    public function unassign($branchId)
    {
        Branch::findOrFail($branchId)->update(['working_period_group_id' => null]);

        return response()->json(['message' => 'Branch unassigned successfully']);
    }
}
