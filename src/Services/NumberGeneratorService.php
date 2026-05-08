<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Generates the next sequential `number` value for a CRM model in a
 * race-condition-safe way using an atomic cache lock + a cached counter.
 *
 * The cache lock serialises concurrent callers; the cached counter
 * carries the "reserved next number" forward until the underlying
 * INSERT lands in the database, eliminating the window between
 * SELECT MAX() and INSERT in which two requests could otherwise
 * read the same maximum.
 */
class NumberGeneratorService
{
    /**
     * @param  string  $modelClass  Fully qualified Eloquent model class (must have `number` column and SoftDeletes).
     * @param  int  $startAt  Starting number when the table is empty (default 1000 — first issued is 1001).
     * @param  int  $lockSeconds  Maximum lock hold time (safety release).
     * @param  int  $waitSeconds  Maximum time to block waiting for the lock.
     */
    public static function next(string $modelClass, int $startAt = 1000, int $lockSeconds = 10, int $waitSeconds = 5): int
    {
        $key = 'laravel-crm:next-number:'.str_replace('\\', '_', $modelClass);
        $lockKey = $key.':lock';
        $counterKey = $key.':counter';

        return Cache::lock($lockKey, $lockSeconds)->block($waitSeconds, function () use ($modelClass, $counterKey, $startAt) {
            $reserved = Cache::get($counterKey);

            if ($reserved === null) {
                /** @var Model $modelClass */
                $query = $modelClass::query();

                if (method_exists($modelClass, 'bootSoftDeletes')) {
                    $query->withTrashed();
                }

                $last = $query->orderBy('number', 'DESC')->first();
                $reserved = $last ? ((int) $last->number) + 1 : $startAt;
            }

            // Reserve the next slot for the next caller (kept short — long
            // enough to bridge the gap to the actual INSERT, short enough
            // that a stale value self-heals quickly).
            Cache::put($counterKey, $reserved + 1, now()->addMinutes(5));

            return $reserved;
        });
    }

    /**
     * Invalidate the cached counter for a model — call after bulk imports
     * or any operation that inserts numbers outside of this generator.
     */
    public static function reset(string $modelClass): void
    {
        $key = 'laravel-crm:next-number:'.str_replace('\\', '_', $modelClass);
        Cache::forget($key.':counter');
    }
}
