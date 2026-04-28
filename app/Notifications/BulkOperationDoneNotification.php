<?php

namespace App\Notifications;

use App\Models\BulkOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BulkOperationDoneNotification extends Notification
{
    use Queueable;

    public function __construct(public BulkOperation $bulkOperation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
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
