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

}
