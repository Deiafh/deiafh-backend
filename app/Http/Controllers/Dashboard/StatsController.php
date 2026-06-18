<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCart;
use App\Models\CancelReason;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from'         => 'nullable|date',
            'to'           => 'nullable|date',
            'branch_ids'   => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ]);

        $from      = $request->from ? $request->from . ' 00:00:00' : null;
        $to        = $request->to   ? $request->to   . ' 23:59:59' : null;
        $branchIds = $request->input('branch_ids', []);

        $base = Order::query()
            ->when($from,              fn($q) => $q->where('created_at', '>=', $from))
            ->when($to,                fn($q) => $q->where('created_at', '<=', $to))
            ->when(count($branchIds),  fn($q) => $q->whereIn('branch_id', $branchIds));

        // ── Orders ───────────────────────────────────────────────────────────
        $orderCounts = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalOrders    = $orderCounts->sum();
        $pendingOrders  = $orderCounts->get(OrderStatus::PENDING->value,  0);
        $acceptedOrders = $orderCounts->get(OrderStatus::ACCEPTED->value, 0);
        $rejectedOrders = $orderCounts->get(OrderStatus::REJECTED->value, 0);

        // ── Revenue (accepted orders only) ───────────────────────────────────
        $revenueRow = (clone $base)
            ->where('status', OrderStatus::ACCEPTED->value)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as count')
            ->first();

        $revenue       = round($revenueRow->revenue, 2);
        $avgOrderValue = $revenueRow->count > 0
            ? round($revenueRow->revenue / $revenueRow->count, 2)
            : 0;

        // ── Unique clients ────────────────────────────────────────────────────
        $uniqueClients = (clone $base)
            ->distinct('client_phone')
            ->count('client_phone');

        // ── Orders by type ────────────────────────────────────────────────────
        $byType = (clone $base)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        // ── Completion rate ───────────────────────────────────────────────────
        $completionRate = $totalOrders > 0
            ? round(($acceptedOrders / $totalOrders) * 100, 1)
            : 0;

        // ── Cancel reason breakdown ───────────────────────────────────────────
        $cancelStats = (clone $base)
            ->where('status', OrderStatus::REJECTED->value)
            ->selectRaw('cancel_reason_id, COUNT(*) as total')
            ->groupBy('cancel_reason_id')
            ->get();

        $reasonIds   = $cancelStats->whereNotNull('cancel_reason_id')->pluck('cancel_reason_id');
        $reasonNames = CancelReason::whereIn('id', $reasonIds)->pluck('name', 'id');

        $cancelBreakdown = $cancelStats->map(fn($row) => [
            'reason'     => $row->cancel_reason_id
                ? ($reasonNames[$row->cancel_reason_id] ?? 'غير معروف')
                : 'بدون سبب',
            'total'      => $row->total,
        ])->values();

        // ── Top selling items (pending + accepted; excludes rejected) ─────────
        $topItems = OrderCart::whereIn('order_id',
                (clone $base)->where('status', '!=', OrderStatus::REJECTED->value)->pluck('id')
            )
            ->selectRaw('item_name, SUM(item_count) as qty, SUM(item_total_price_with_options) as revenue')
            ->groupBy('item_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        return response()->json([
            'revenue'         => $revenue,
            'avg_order_value' => $avgOrderValue,
            'completion_rate' => $completionRate,
            'orders' => [
                'total'    => $totalOrders,
                'pending'  => $pendingOrders,
                'accepted' => $acceptedOrders,
                'rejected' => $rejectedOrders,
            ],
            'clients'   => $uniqueClients,
            'by_type'   => [
                'delivery' => $byType->get(OrderType::DELIVERY->value, 0),
                'pickup'   => $byType->get(OrderType::PICK_UP->value,  0),
            ],
            'top_items'        => $topItems,
            'cancel_breakdown' => $cancelBreakdown,
        ]);
    }
}
