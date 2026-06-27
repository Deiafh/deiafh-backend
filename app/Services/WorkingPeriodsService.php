<?php

namespace App\Services;

use App\Models\WorkingPeriod;
use Carbon\Carbon;

class WorkingPeriodsService
{
    private const daysMapping = ["Saturday" => 0, "Sunday" => 1, "Monday" => 2, "Tuesday" => 3, "Wednesday" => 4, "Thursday" => 5, "Friday" => 6];

    public static function getCurrent()
    {
        $week = date("l");

        $current_date = self::daysMapping[$week] . date("His");

        return $current_date;
    }

    /**
     * The actual datetime the currently-active working window began, or null
     * if no window is open right now. Used to scope the live-orders page to the
     * current working period (orders created at/after this instant).
     *
     * Working periods are weekly windows encoded as "<dayIndex><HHMMSS>" where
     * Saturday=0 .. Friday=6. If several windows overlap, the earliest start
     * wins so no in-window order is hidden.
     */
    public static function getCurrentPeriodStart(): ?Carbon
    {
        $now          = Carbon::now();
        $currentIndex = self::currentDayIndex($now);
        $current      = $currentIndex . $now->format('His'); // 7-char encoded "now"

        $earliest = null;

        foreach (WorkingPeriod::all(['from_date', 'to_date']) as $period) {
            $from = $period->from_date;
            $to   = $period->to_date;

            // Same-week window vs. one that wraps past the week boundary.
            $active = $from <= $to
                ? ($current >= $from && $current <= $to)
                : ($current >= $from || $current <= $to);

            if (! $active) {
                continue;
            }

            $start = self::decodeToRecentDateTime($from, $now);

            if ($earliest === null || $start->lt($earliest)) {
                $earliest = $start;
            }
        }

        return $earliest;
    }

    /**
     * The datetime the currently-active working window will close, or null if no
     * window is open right now for the given group. When several active windows
     * overlap, the latest end wins so the countdown reflects the real closing time.
     */
    public static function getCurrentPeriodEndForGroup(?int $groupId): ?Carbon
    {
        $now          = Carbon::now();
        $currentIndex = self::currentDayIndex($now);
        $current      = $currentIndex . $now->format('His');

        $query = WorkingPeriod::query();
        $groupId === null
            ? $query->whereNull('working_period_group_id')
            : $query->where('working_period_group_id', $groupId);

        $latestEnd = null;

        foreach ($query->get(['from_date', 'to_date']) as $period) {
            $from = $period->from_date;
            $to   = $period->to_date;

            $active = $from <= $to
                ? ($current >= $from && $current <= $to)
                : ($current >= $from || $current <= $to);

            if (! $active) {
                continue;
            }

            $end = self::decodeToUpcomingDateTime($to, $now);

            if ($latestEnd === null || $end->gt($latestEnd)) {
                $latestEnd = $end;
            }
        }

        return $latestEnd;
    }

    /** Soonest real datetime (>= now) matching an encoded weekday+time boundary. */
    private static function decodeToUpcomingDateTime(string $encoded, Carbon $now): Carbon
    {
        $dayIndex = (int) substr($encoded, 0, 1);
        $hour     = (int) substr($encoded, 1, 2);
        $minute   = (int) substr($encoded, 3, 2);
        $second   = (int) substr($encoded, 5, 2);

        $daysForward = ((($dayIndex - self::currentDayIndex($now)) % 7) + 7) % 7;
        $candidate   = $now->copy()->addDays($daysForward)->setTime($hour, $minute, $second);

        if ($candidate->lt($now)) {
            $candidate->addDays(7);
        }

        return $candidate;
    }

    /** Most recent real datetime (<= now) matching an encoded weekday+time boundary. */
    private static function decodeToRecentDateTime(string $encoded, Carbon $now): Carbon
    {
        $dayIndex = (int) substr($encoded, 0, 1);
        $hour     = (int) substr($encoded, 1, 2);
        $minute   = (int) substr($encoded, 3, 2);
        $second   = (int) substr($encoded, 5, 2);

        $daysBack  = ((self::currentDayIndex($now) - $dayIndex) % 7 + 7) % 7;
        $candidate = $now->copy()->subDays($daysBack)->setTime($hour, $minute, $second);

        if ($candidate->gt($now)) {
            $candidate->subDays(7);
        }

        return $candidate;
    }

    /** Saturday=0 .. Friday=6, matching the working-period encoding. */
    private static function currentDayIndex(Carbon $now): int
    {
        return ((int) $now->format('w') + 1) % 7;
    }

    public static function isAvailableGeneralWorkingPeriod() {
        // A general (ungrouped) window is open exactly when one is currently active.
        return self::getCurrentPeriodEndForGroup(null) !== null;
    }
}
