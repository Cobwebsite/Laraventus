<?php

namespace Aventus\Laraventus\Tools;

use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class Type
{
    /**
     * List all types that don't need a $type
     * @var string[]
     */
    private static $avoidEnrich = [Carbon::class, DateTime::class];

    /**
     * Add a type that don't need a $type
     * @param string $className
     * @return void
     */
    public static function addAvoidEnrich(string $className)
    {
        if (in_array($className, self::$avoidEnrich))
            return;
        self::$avoidEnrich[] = $className;
    }
    public static function enrich($data): mixed
    {
        if (is_a($data, Collection::class, true)) {
            // Parcourir les tableaux pour enrichir récursivement
            foreach ($data as &$item) {
                $item = Type::enrich($item);
            }
        } else if (is_object($data) && !($data instanceof UnitEnum)) {
            foreach (self::$avoidEnrich as $class) {
                if (is_a($data, $class, true)) {
                    return $data;
                }
            }
            // Ajouter la clé `$type` si l'objet n'est pas déjà enrichi
            $dataAsArray = (array) $data;
            if (!array_key_exists('$type', $dataAsArray)) {
                try {
                    $data->{'$type'} = str_replace("\\", ".", get_class($data));
                } catch (Exception $e) {
                    Console::log("---- enrich ----");
                    Console::dump(get_class($data));
                    throw $e;
                }
            }

            if ($data instanceof Model) {
                $array = $data->toArray();
                foreach ($array as $key => $value) {
                    Type::enrich($data->$key);
                }
            } else {
                foreach ($data as $key => $value) {
                    Type::enrich($data->$key);
                }
            }
        } elseif (is_array($data)) {
            // Parcourir les tableaux pour enrichir récursivement
            foreach ($data as &$item) {
                $item = Type::enrich($item);
            }
        }

        return $data;
    }
}
