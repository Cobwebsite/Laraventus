<?php

namespace Aventus\Laraventus\Models;

use Aventus\Laraventus\Attributes\Column;
use Aventus\Laraventus\Tools\Console;
use Illuminate\Database\Eloquent\Model;
use ReflectionAttribute;
use ReflectionClass;
use Throwable;

abstract class AventusModel extends Model
{
    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->timestamps = config('laraventus.model.timestamps') ?? true;
        $unfillable = array_diff_key($attributes, array_flip($this->getFillable()));
        $attrs = [];
        foreach ($attributes as $key => $value) {
            if (!array_key_exists($key, $unfillable)) {
                $attrs[$key] = $value;
            }
        }
        parent::__construct($attrs);

        if (count($unfillable) > 0) {
            $deny = $this->preventKeys();
            $reflection = new ReflectionClass(get_called_class());
            foreach ($unfillable as $key => $value) {
                if (!in_array($key, $deny)) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    /**
     * @return string[]
     */
    protected function preventKeys(): array
    {
        return ["\$type"];
    }


    /**
     * Perform any actions required before the model boots.
     *
     * @return void
     */
    protected static function booting()
    {
        //self::loadingAttributes();
    }


    protected static $dbAttributes = [];
    protected static function loadingAttributes()
    {
        $reflection = new \ReflectionClass(get_called_class());
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $isColumn = count($property->getAttributes(
                Column::class,
                ReflectionAttribute::IS_INSTANCEOF
            )) > 0;

            if ($isColumn) {
                self::$dbAttributes[] = $property->getName();
            }
        }
    }

    public function save(array $options = [])
    {
        parent::save($options);
    }
}
