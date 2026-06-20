<?php

namespace App\Notifications;

use App\Models\BulkOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class BulkActionCompleted extends Notification
{
    use Queueable;

    public function __construct(public BulkOperation $bulkOperation) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $op = $this->bulkOperation;

        return [
            'type'  => 'bulk_action_completed',
            'title' => 'Bulk action completed',
            'body'  => ucfirst($op->type) . ": {$op->success_count} succeeded, {$op->failed_count} failed.",
            'url'   => '/posts',
            'data'  => [
                'action'           => $op->type,
                'total'            => $op->total,
                'succeeded'        => $op->success_count,
                'failed'           => $op->failed_count,
                'bulk_operation_id' => $op->id,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
