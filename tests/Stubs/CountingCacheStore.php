<?php

namespace VentureDrake\LaravelCrm\Tests\Stubs;

use Illuminate\Cache\ArrayStore;

/**
 * An array store that counts how often it is asked for something.
 *
 * Exists so a test can assert on the number of round-trips a code path makes
 * to the cache. On the array driver those are free, which is exactly why the
 * suite cannot otherwise see the difference between one lookup per request and
 * one per view — on redis or the database driver that gap is the whole cost.
 *
 * Counters are static so a test can read them without a handle on the instance
 * the CacheManager built.
 */
class CountingCacheStore extends ArrayStore
{
    public static int $reads = 0;

    public static int $writes = 0;

    public static function reset(): void
    {
        static::$reads = 0;
        static::$writes = 0;
    }

    public function get($key)
    {
        static::$reads++;

        return parent::get($key);
    }

    public function many(array $keys)
    {
        static::$reads += count($keys);

        return parent::many($keys);
    }

    public function put($key, $value, $seconds)
    {
        static::$writes++;

        return parent::put($key, $value, $seconds);
    }

    public function forever($key, $value)
    {
        static::$writes++;

        return parent::forever($key, $value);
    }

    public function increment($key, $value = 1)
    {
        static::$writes++;

        return parent::increment($key, $value);
    }

    public function forget($key)
    {
        static::$writes++;

        return parent::forget($key);
    }
}
