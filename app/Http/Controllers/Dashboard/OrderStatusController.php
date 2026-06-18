<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status'           => ['required', 'in:' . implode(',', array_column(OrderStatus::cases(), 'value'))],
            'cancel_reason_id' => 'nullable|exists:cancel_reasons,id',
            'cancel_notes'     => 'nullable|string|max:1000',
        ]);

        $update = ['status' => $data['status']];

        if ($data['status'] === OrderStatus::REJECTED->value) {
            $update['cancel_reason_id'] = $data['cancel_reason_id'] ?? null;
            $update['cancel_notes']     = $data['cancel_notes'] ?? null;
        } else {
            $update['cancel_reason_id'] = null;
            $update['cancel_notes']     = null;
        }

        $order->update($update);

        return response()->json($order->load('cancelReason'));
    }
}
