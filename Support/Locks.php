<?php

namespace Deadbolt\Support;

class Locks
{
    public static function expectedKey($lock, $locks = null)
    {
        if (! is_string($lock) || $lock === '' || strlen($lock) > 191) {
            return null;
        }

        if ($locks === null) {
            $locks = ee()->config->item('deadbolt_locks');
        }

        if (! is_array($locks) || ! array_key_exists($lock, $locks)) {
            return null;
        }

        $expected = $locks[$lock];
        if (! is_string($expected) || $expected === '') {
            return null;
        }

        return $expected;
    }

    public static function matches($lock, $submitted, $locks = null)
    {
        $expected = self::expectedKey($lock, $locks);

        return $expected !== null
            && is_string($submitted)
            && hash_equals($expected, $submitted);
    }
}
