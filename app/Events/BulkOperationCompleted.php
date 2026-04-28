<?php

namespace App\Events;

use App\Models\BulkOperation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkOperationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BulkOperation $bulkOperation) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->bulkOperation->user_id);
    }

    public function broadcastWith(): array
    {
        return [
            'type'         => $this->bulkOperation->type,
            'total'        => $this->bulkOperation->total,
            'success'      => $this->bulkOperation->success_count,
            'failed'       => $this->bulkOperation->failed_count,
            'completed_at' => $this->bulkOperation->completed_at?->toISOString(),
        ];
    }
}
