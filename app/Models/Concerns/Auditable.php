<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Jejak perubahan (pengganti chatter/tracking Odoo).
 *
 * Kolom pada $tracked dicatat beserta nilai lama → baru; kolom lain hanya namanya.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => AuditLog::record('dibuat', $model));

        static::updated(function (Model $model) {
            $changes = [];

            foreach (array_keys($model->getChanges()) as $field) {
                if (in_array($field, ['updated_at', 'created_at'], true)) {
                    continue;
                }

                $changes[$field] = in_array($field, $model->tracked ?? [], true)
                    ? ['dari' => static::auditValue($model->getOriginal($field)), 'ke' => static::auditValue($model->getAttribute($field))]
                    : null;
            }

            if ($changes !== []) {
                AuditLog::record('diubah', $model, $changes);
            }
        });

        static::deleted(fn (Model $model) => AuditLog::record('dihapus', $model));
    }

    protected static function auditValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i'),
            default => $value,
        };
    }

    public function auditLabel(): string
    {
        return trim(($this->code ?? $this->number ?? '').' '.($this->name ?? ''));
    }
}
