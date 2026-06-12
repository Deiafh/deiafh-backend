<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\WorkingPeriod;
use App\Models\WorkingPeriodGroup;
use Illuminate\Http\Request;

class WorkingPeriodController extends Controller
{
    private static array $encodeMap = ['saturday' => 0, 'sunday' => 1, 'monday' => 2, 'tuesday' => 3, 'wednesday' => 4, 'thursday' => 5, 'friday' => 6];

    public function store(Request $request, $groupId)
    {
        $request->validate([
            'dayStart'  => 'required|string',
            'hourStart' => 'required|string',
            'dayEnd'    => 'required|string',
            'hourEnd'   => 'required|string',
        ]);

        WorkingPeriodGroup::findOrFail($groupId);

        $period = WorkingPeriod::create([
            'working_period_group_id' => $groupId,
            'from_date' => $this->encode($request->dayStart, $request->hourStart),
            'to_date'   => $this->encode($request->dayEnd, $request->hourEnd),
        ]);

        return response()->json([
            'id'        => $period->id,
            'dayStart'  => ucfirst(strtolower($request->dayStart)),
            'hourStart' => $request->hourStart,
            'dayEnd'    => ucfirst(strtolower($request->dayEnd)),
            'hourEnd'   => $request->hourEnd,
        ], 201);
    }

    public function update(Request $request, $groupId, $periodId)
    {
        $request->validate([
            'dayStart'  => 'required|string',
            'hourStart' => 'required|string',
            'dayEnd'    => 'required|string',
            'hourEnd'   => 'required|string',
        ]);

        $period = WorkingPeriod::where('id', $periodId)
            ->where('working_period_group_id', $groupId)
            ->firstOrFail();

        $period->update([
            'from_date' => $this->encode($request->dayStart, $request->hourStart),
            'to_date'   => $this->encode($request->dayEnd, $request->hourEnd),
        ]);

        return response()->json([
            'id'        => $period->id,
            'dayStart'  => ucfirst(strtolower($request->dayStart)),
            'hourStart' => $request->hourStart,
            'dayEnd'    => ucfirst(strtolower($request->dayEnd)),
            'hourEnd'   => $request->hourEnd,
        ]);
    }

    public function destroy($groupId, $periodId)
    {
        WorkingPeriod::where('id', $periodId)
            ->where('working_period_group_id', $groupId)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    private function encode(string $day, string $hour): string
    {
        $dayIndex = self::$encodeMap[strtolower($day)];
        $parts    = explode(':', $hour);

        return $dayIndex . str_pad($parts[0], 2, '0', STR_PAD_LEFT) . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '00';
    }
}
