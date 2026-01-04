<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PesananMasukUntukTenant implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public Collection $items;
    public string $kodePesanan;
    /**
     * Create a new event instance.
     * @param int $tenantId
     * @param Collection $items
     * @param string $kodePesanan
     */
    public function __construct(int $tenantId, Collection $items, string $kodePesanan)
    {
        $this->tenantId = $tenantId;
        $this->items = $items;
        $this->kodePesanan = $kodePesanan;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    public function broadcastAs() : string
    {
        return 'PesananMasukUntukTenant';
    }
}
