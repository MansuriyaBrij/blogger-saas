# Labels Module

## Responsibility

Manages the label taxonomy for each blog — tracks post counts, and provides rename, merge, and delete operations that update both the local DB and Blogger via background jobs.

## Classes

| Class | Role |
|---|---|
| `App\Http\Controllers\LabelController` | `index`, `rename`, `merge`, `destroy` HTTP actions |
| `App\Jobs\RenameLabelJob` | Calls Blogger API to update the label name on every affected post |
| `App\Jobs\MergeLabelJob` | Moves all posts from source label to target label; deletes source |
| `App\Jobs\DeleteLabelJob` | Removes the label from all posts on Blogger; deletes local label record |
| `App\Models\Label` | Belongs to `User` and `BloggerAccount`; stores aggregated `post_count` |

## Key Methods

| Method | Class | Description |
|---|---|---|
| `index()` | `LabelController` | Returns labels for the active blog, ordered by `post_count` desc |
| `rename(Request, Label)` | `LabelController` | Validates new name; dispatches `RenameLabelJob` |
| `merge(Request)` | `LabelController` | Validates `source_id` + `target_id`; dispatches `MergeLabelJob` |
| `destroy(Label)` | `LabelController` | Dispatches `DeleteLabelJob` |
| `handle()` | `RenameLabelJob` | Updates `posts.labels` JSON for all matching posts; calls `BloggerService::updatePost()` per post |
| `handle()` | `MergeLabelJob` | Updates `posts.labels` to replace source with target; deletes source `Label`; recalculates `post_count` on target |
| `handle()` | `DeleteLabelJob` | Strips label from `posts.labels` JSON; calls `BloggerService::updatePost()` per post; deletes `Label` |

## Models

### Label

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users |
| `blogger_account_id` | bigint | FK → blogger_accounts, cascade delete |
| `name` | string | Label text as stored on Blogger |
| `post_count` | integer | Denormalised count; recalculated on sync |

## Notes

- Labels are denormalised — `post_count` is recalculated every sync rather than maintained via triggers.
- The `labels` column on `Post` is a JSON array of strings; the `Label` model is a separate aggregation table, not a pivot.
- Rename and delete operations on large blogs may affect hundreds of posts — always dispatched to the `jobs` Horizon queue with `$tries = 3`.
- Merge must verify `source_id !== target_id` and that both belong to the same `blogger_account_id`.
