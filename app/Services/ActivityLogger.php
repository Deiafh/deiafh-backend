<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ActivityLogger
{
    /**
     * Persist one activity log entry.
     */
    public static function record(?User $user, ?string $actionKey, ?string $description, Request $request, array $properties = []): void
    {
        ActivityLog::create([
            'user_id'     => $user?->id,
            'user_name'   => $user?->name,
            // Actions by super-admins or hidden users are only visible to super-admins.
            'concealed'   => $user ? ($user->hasRole('super_admin') || (bool) $user->hidden) : false,
            'action_key'  => $actionKey,
            'description' => $description ?: self::describe($actionKey),
            'method'      => $request->method(),
            'url'         => $request->path(),
            'properties'  => $properties ?: null,
            'ip'          => $request->ip(),
        ]);
    }

    /**
     * Build an Arabic description from a permission key using the catalog
     * (e.g. "users.add" → "المستخدمين - إضافة").
     */
    public static function describe(?string $key): string
    {
        if (! $key) {
            return 'إجراء';
        }

        foreach (Config::get('permissions.groups', []) as $group) {
            if (isset($group['permissions'][$key])) {
                return $group['label'] . ' - ' . $group['permissions'][$key];
            }
        }

        return $key;
    }
}
