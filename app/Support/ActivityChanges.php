<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Request-scoped collector of model field changes (old → new), populated by
 * the wildcard Eloquent listeners registered in AppServiceProvider. The
 * activity-log middleware reads it after the request to attach a diff.
 */
class ActivityChanges
{
    private array $entries = [];

    private const IGNORE = ['updated_at', 'created_at', 'remember_token', 'password'];

    public function record(string $event, Model $model): void
    {
        // Never track the log table itself.
        if ($model instanceof ActivityLog) {
            return;
        }

        $label = class_basename($model);
        $id = $model->getKey();

        if ($event === 'updated') {
            $fields = [];
            foreach ($model->getChanges() as $key => $new) {
                if (in_array($key, self::IGNORE, true)) {
                    continue;
                }
                $fields[$key] = ['old' => $model->getOriginal($key), 'new' => $new];
            }
            if ($fields) {
                $this->entries[] = compact('label', 'id') + ['event' => 'updated', 'fields' => $fields];
            }
        } elseif ($event === 'created') {
            $fields = $this->cleanAttributes($model->getAttributes());
            $this->entries[] = compact('label', 'id') + ['event' => 'created', 'fields' => $fields];
        } elseif ($event === 'deleted') {
            $fields = $this->cleanAttributes($model->getOriginal());
            $this->entries[] = compact('label', 'id') + ['event' => 'deleted', 'fields' => $fields];
        }
    }

    public function all(): array
    {
        return $this->entries;
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    private function cleanAttributes(array $attributes): array
    {
        return collect($attributes)
            ->except(array_merge(self::IGNORE, ['id']))
            ->all();
    }
}
