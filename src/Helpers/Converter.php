<?php

namespace Aventus\Laraventus\Helpers;

class Converter
{
    public static function isBool(mixed $value): bool
    {
        if ($value === true || $value === 1 || $value === 'true' || $value === '1') {
            return true;
        }
        if ($value === false || $value === 0 || $value === 'false' || $value === '0') {
            return true;
        }
        return false;
    }
    public static function toBool(string|bool|int|null $value): bool
    {
        if ($value === true || $value === 1 || $value === 'true' || $value === '1') {
            return true;
        }
        return false;
    }

    public static function boolToInt($value): int
    {
        $value = Converter::toBool($value);
        if ($value) {
            return 1;
        }
        return 0;
    }


    public static function toBoolNullable(string|bool|int|null $value): ?bool
    {
        if($value === null) return null;
        if ($value === true || $value === 1 || $value === 'true') {
            return true;
        }
        return false;
    }

    public static function boolToIntNullable($value): ?int
    {
        if($value === null) return null;
        $value = Converter::toBool($value);
        if ($value) {
            return 1;
        }
        return 0;
    }
}