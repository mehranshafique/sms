<?php

namespace App\Traits;

use App\Services\AuditLogger;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            AuditLogger::log('Create', class_basename($model), "Created " . class_basename($model), null, $model->toArray());
        });

        static::updated(function ($model) {
            AuditLogger::log('Update', class_basename($model), "Updated " . class_basename($model), $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            AuditLogger::log('Delete', class_basename($model), "Deleted " . class_basename($model), $model->toArray(), null);
        });
    }
}