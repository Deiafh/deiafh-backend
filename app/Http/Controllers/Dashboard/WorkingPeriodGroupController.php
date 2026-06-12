<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\WorkingPeriod;
use App\Models\WorkingPeriodGroup;
use Illuminate\Http\Request;

class WorkingPeriodGroupController extends Controller
{
    private static array $decodeMap = [0 => 'Saturday', 1 => 'Sunday', 2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday'];
    private static array $encodeMap = ['saturday' => 0, 'sunday' => 1, 'monday' => 2, 'tuesday' => 3, 'wednesday' => 4, 'thursday' => 5, 'friday' => 6];

    public function index()
    {
        $groups = WorkingPeriodGroup::with(['workingPeriods', 'branches'])->get();

        return response()->json($groups->map(fn($group) => [
            'id' => $group->id,
            'name' => $group->name,
            'workingPeriods' => $group->workingPeriods->map(fn($p) => $this->decodePeriod($p)),
            'branches' => $group->branches->map(fn($b) => ['id' => $b->id, 'title' => $b->title]),
        ]));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $group = WorkingPeriodGroup::create(['name' => $request->name]);

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'workingPeriods' => [],
            'branches' => [],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);

        WorkingPeriodGroup::findOrFail($id)->update(['name' => $request->name]);

        return response()->json(['message' => 'Updated successfully']);
    }

    public function destroy($id)
    {
        WorkingPeriodGroup::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    private function decodePeriod(WorkingPeriod $period): array
    {
        $from = $period->from_date;
        $to   = $period->to_date;

        return [
            'id'        => $period->id,
            'dayStart'  => self::$decodeMap[(int) $from[0] % 7],
            'hourStart' => substr($from, 1, 2) . ':' . substr($from, 3, 2),
            'dayEnd'    => self::$decodeMap[(int) $to[0] % 7],
            'hourEnd'   => substr($to, 1, 2) . ':' . substr($to, 3, 2),
        ];
    }
}
