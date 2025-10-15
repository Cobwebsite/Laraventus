<?php

namespace Aventus\Laraventus\Resources;

use Aventus\Laraventus\Models\AventusModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as Collection2;

/**
 * @template T of AventusModel = AventusModel
 */
abstract class AventusModelResource extends AventusResource
{
    /**
     * Create a collection for the current resource
     * @template TChild of static
     * @param T[]|Collection<int, T>|Collection2<int, T> $items
     * @return TChild[]
     */
    public static function collection(array|Collection|Collection2 $items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = new static($item);
        }
        return $result;
    }

    /**
     * @param T $item
     */
    public function __construct($item)
    {
        $this->bind($item);
    }

    /**
     * Define your bindings
     * @param T $item
     * @return void
     */
    protected abstract function bind($item): void;
}
