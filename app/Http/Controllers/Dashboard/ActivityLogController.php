<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->size ?? 15;
        $orderBy = $request->orderBy ?? 'id';
        $orderDir = $request->orderDir ?? 'desc';

        $viewerIsSuperAdmin = Auth::guard('api')->user()->hasRole(
            Config::get('permissions.super_admin_role', 'super_admin')
        );

        $logs = ActivityLog::query()
            // Conceal super-admin / hidden-user activity from everyone else.
            ->when(! $viewerIsSuperAdmin, fn($q) => $q->where('concealed', false))
            ->when($request->user, fn($q) => $q->where('user_name', 'like', '%' . $request->user . '%'))
            ->when($request->action_key, fn($q) => $q->where('action_key', $request->action_key))
            ->when($request->method, fn($q) => $q->where('method', $request->method))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('description', 'like', '%' . $s . '%')
                        ->orWhere('user_name', 'like', '%' . $s . '%')
                        ->orWhere('url', 'like', '%' . $s . '%');
                });
            })
            ->orderBy($orderBy, $orderDir)
            ->paginate($size);

        $logs->getCollection()->transform(fn($log) => [
            'id'          => $log->id,
            'user_name'   => $log->user_name ?? 'مستخدم محذوف',
            'description' => $log->description,
            'action_key'  => $log->action_key,
            'method'      => $log->method,
            'url'         => $log->url,
            'properties'  => $log->properties,
            'ip'          => $log->ip,
            'created_at'  => $log->created_at?->format('Y-m-d H:i:s'),
        ]);

        return response()->json($logs);
    }
}
