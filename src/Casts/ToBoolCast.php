<?php

namespace Aventus\Laraventus\Casts;

use Aventus\Laraventus\Helpers\Converter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ToBoolCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return Converter::toBool($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return Converter::boolToInt($value);
    }
}