<?php

namespace App\Http\Controllers;

use App\Jobs\BulkOperationJob;
use App\Models\BulkOperation;
use App\Models\Post;
use Illuminate\Http\Request;

class BulkController extends Controller
{
    public function handle(Request $request)
    {
        $userId = auth()->id();

        $request->validate([
            'action'     => ['required', 'in:publish,draft,delete,label_add,label_remove'],
            'post_ids'   => ['required', 'array'],
            'post_ids.*' => ['integer'],
            'label_name' => ['required_if:action,label_add', 'required_if:action,label_remove', 'nullable', 'string'],
        ]);

        $postIds = $request->post_ids;

        $belongsToUser = Post::whereIn('id', $postIds)
            ->where('user_id', $userId)
            ->count();

        abort_if($belongsToUser !== count($postIds), 403);

        $bloggerAccountId = Post::whereIn('id', $postIds)->value('blogger_account_id');

        $bulk = BulkOperation::create([
            'user_id'            => $userId,
            'blogger_account_id' => $bloggerAccountId,
            'type'               => $request->action,
            'total'              => count($postIds),
            'success_count'      => 0,
            'failed_count'       => 0,
            'error_log'          => [],
            'status'             => 'pending',
        ]);

        dispatch(new BulkOperationJob($bulk, $postIds, $request->label_name));

        return response()->json([
            'message' => 'Bulk operation queued',
            'id'      => $bulk->id,
        ]);
    }

    public function status(BulkOperation $bulkOperation)
    {
        abort_unless($bulkOperation->user_id === auth()->id(), 403);

        return response()->json($bulkOperation);
    }
}
