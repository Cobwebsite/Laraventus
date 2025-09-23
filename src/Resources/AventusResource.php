<?php

namespace Aventus\Laraventus\Resources;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;


/**
 * Summary of AventusResource
 */
abstract class AventusResource implements JsonSerializable
{

    private bool $useLastSerialize = false;
    public function setUseLastSerialize(bool $value)
    {
        $this->useLastSerialize = $value;
    }
    private array|null $lastSerialize = null;
    public function jsonSerialize(): array
    {
        if ($this->useLastSerialize && $this->lastSerialize != null) {
            return $this->lastSerialize;
        }
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        $result = [
            "\$type" => str_replace("\\", ".", get_class($this))
        ];
        foreach ($props as $prop) {
            $name = $prop->getName();
            if (isset($this->{$name})) {
                $result[$name] = $this->{$name};
            } else {
                $result[$name] = null;
            }
        }

        $this->lastSerialize = $result;

        return $result;
    }
}
