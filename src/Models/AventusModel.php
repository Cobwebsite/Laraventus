<?php

namespace Aventus\Laraventus\Models;

use Aventus\Laraventus\Attributes\Column;
use Aventus\Laraventus\Tools\Console;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Throwable;

abstract class AventusModel extends Model
{
    /** @var array<string, ModelInfo> */
    private static array $info = [];
    private bool $only_fillable = true;
    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->timestamps = config('laraventus.model.timestamps') ?? true;
        $this->only_fillable = config('laraventus.model.only_fillable') ?? true;
        $unfillable = array_diff_key($attributes, array_flip($this->getFillable()));
        $attrs = [];
        foreach ($attributes as $key => $value) {
            if (!array_key_exists($key, $unfillable)) {
                $attrs[$key] = $value;
            }
        }
        parent::__construct($attrs);

        if (!$this->only_fillable && count($unfillable) > 0) {
            $deny = $this->preventKeys();
            foreach ($unfillable as $key => $value) {
                if(!$this->isRelation($key)) {
                    if (!in_array($key, $deny)) {
                        $this->{$key} = $value;
                    }
                }
                else {
                    if(is_array($value)) {
                        $value = new Collection($value);
                    }
                    $this->setRelation($key, $value);
                }
            }
        }
    }

    /**
     * @return string[]
     */
    protected function preventKeys(): array
    {
        return ["\$type", "__path"];
    }

    // public function analyse()
    // {
    //     $className = get_called_class();
    //     if (array_key_exists($className, self::$info)) {
    //         return;
    //     }
    //     $info = new ModelInfo();
    //     self::$info[$className] = $info;

    //     $reflection = new ReflectionClass($className);
    //     $methods = $reflection->getMethods(ReflectionProperty::IS_PUBLIC);

    //     foreach ($methods as $method) {
    //         $returnType = $method->getReturnType();
    //         if ($returnType instanceof ReflectionNamedType) {
    //             if ($returnType->getName() == HasMany::class) {
    //                 $name = $method->getName();
    //                 $info->hasMany[] = $name;
    //             }
    //         }
    //     }
    // }

    // public function save(array $options = []): bool
    // {
    //     $this->analyse();
    //     return parent::save($options);
    // }
}
