<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ActivityLogObserver
{
    /** @var list<string> */
    private const SENSITIVE_FIELDS = [
        'password',
        'mikrotik_password',
        'api_key',
        'api_secret',
        'encrypted_value',
        'raw_payload',
        'code_hash',
        'recipient',
        'requested_value',
        'remember_token',
        'token',
        'secret',
        'secret_key',
        'server_key',
        'client_key',
        'callback_token',
        'webhook_secret',
        'host',
        'api_port',
        'username',
    ];

    public function created(Model $model): void
    {
        $attributes = Arr::except($model->getAttributes(), self::SENSITIVE_FIELDS);

        $this->write($model, 'created', $attributes);
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), [...self::SENSITIVE_FIELDS, 'updated_at']);

        if ($changes === []) {
            return;
        }

        $before = Arr::only($model->getOriginal(), array_keys($changes));

        $this->write($model, 'updated', [
            'before' => $before,
            'after' => $changes,
        ]);
    }

    public function deleted(Model $model): void
    {
        $attributes = Arr::except($model->getAttributes(), self::SENSITIVE_FIELDS);

        $this->write($model, 'deleted', $attributes);
    }

    /** @param array<string, mixed> $properties */
    private function write(Model $model, string $event, array $properties): void
    {
        $branchId = $model->getAttribute('branch_id');

        if ($model instanceof Branch) {
            $branchId = $model->getKey();
        } elseif ($branchId === null && method_exists($model, 'branch')) {
            $branchId = $model->branch?->getKey();
        } elseif ($branchId === null && method_exists($model, 'customer')) {
            $branchId = $model->customer?->branch_id;
        } elseif ($branchId === null && method_exists($model, 'ticket')) {
            $branchId = $model->ticket?->branch_id;
        }

        $request = app()->runningInConsole() ? null : request();

        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'branch_id' => $branchId,
            'event' => $event,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'description' => class_basename($model).' '.$event,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
