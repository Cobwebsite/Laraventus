<?php

namespace Aventus\Laraventus\Tools;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

class Json
{
    public static function toJsonObj($obj)
    {
        return self::normalize($obj, $obj);
    }

    private static function normalize($value, $root)
    {
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn($item) => self::normalize($item, $root), $value);
        }

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

    public static function toClassObj($obj)
    {
        if (is_null($obj) || is_scalar($obj)) {
            return $obj;
        }

        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $obj[$key] = self::toClassObj($value);
            }
            if (isset($obj['$type'])) {
                $type = str_replace(".", "\\", $obj['$type']);
                if (class_exists($type)) {
                    if (is_a($type, Model::class, true)) {
                        return new $type($obj);
                    } else {
                       $result = new $type();
                        foreach ($obj as $key => $value) {
                            $result->{$key} = $value;
                        }
                        return $result;
                    }
                }
            }
        }

        else if (is_object($obj)) {
            foreach ($obj as $key => $value) {
                $obj->$key = self::toClassObj($value);
            }
            if (isset($obj->{'$type'})) {
                $type = str_replace(".", "\\", $obj->{'$type'});
                if (class_exists($type)) {
                    if (is_a($type, Model::class, true)) {
                        return new $type($obj);
                    } else {
                        $result = new $type();
                        foreach ($obj as $key => $value) {
                            $result->{$key} = $value;
                        }
                        return $result;
                    }
                }
            }
        }

        return $obj;
    }
}
