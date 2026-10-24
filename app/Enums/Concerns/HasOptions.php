<?php

namespace App\Enums\Concerns;

trait HasOptions
{
    /** @return array<string, string> nilai => label, untuk select dan validasi. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
