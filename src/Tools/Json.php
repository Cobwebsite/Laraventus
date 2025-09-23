<?php

namespace Aventus\Laraventus\Tools;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class Json
{
    public static function toJsonObj($obj)
    {
        return self::normalize($obj, $obj);
    }

    private static function normalize($value, $root)
    {
        // null ou scalaire
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        // tableau → normaliser chaque élément
        if (is_array($value)) {
            return array_map(fn($item) => self::normalize($item, $root), $value);
        }

        // objet qui implémente JsonSerializable
        if ($value != $root && $value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if ($value != $root && $value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value != $root && $value instanceof Jsonable) {
            return json_decode($value->toJson(), true);
        }

        $vars = get_object_vars($value);
        return array_map(fn($prop) => self::normalize($prop, $root), $vars);
    }
}
