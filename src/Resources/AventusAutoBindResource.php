<?php

namespace Aventus\Laraventus\Resources;

use Aventus\Laraventus\Models\AventusModel;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;


/**
 * @template T of AventusModel = AventusModel
 */
abstract class AventusAutoBindResource extends AventusModelResource implements JsonSerializable
{

    /**
     * @param T $item
     */
    public function __construct($item)
    {
        $this->autoBind($item, $this->avoidAutobBind());
        $this->bind($item);
    }

    /**
     * Define if the resource must be autobinded
     * If you need to avoid some keys you can override the function `avoidAutobBind`
     * @return void
     */
    protected abstract function needAutoBind(): bool;


    /**
     * Define the keys that must not be auto binded
     * @return string[]
     */
    protected function avoidAutobBind(): array
    {
        return [];
    }
    
    /**
     * Summary of autoBind
     * @param T $item
     * @param string[] $avoids
     * @return void
     */
    protected function autoBind(object $item, array $avoids = [])
    {
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $name = $prop->getName();
            if (in_array($name, $avoids)) {
                continue;
            }

            if (isset($item->{$name})) {
                $this->{$name} = $item->{$name};
            }
        }
    }
}