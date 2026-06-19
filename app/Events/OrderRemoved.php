<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderRemoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $orderId, public int $branchId)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('dashboard.orders.branch.' . $this->branchId);
    }

    public function broadcastAs(): string
    {
        return 'order.removed';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->orderId];
    }
}
