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
    public array $__links = [];
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
        parent::__construct($attributes);
    }

    /**
     * @return string[]
     */
    protected function preventKeys(): array
    {
        return ["\$type", "__path"];
    }

    public function syncHasMany($name)
    {
        if (!isset($this->id) || $this->id == 0) {
            $this->{$name}()->saveMany($this->{$name});
        } else {
            $currentItem = self::find($this->id);
            $existingIds = $currentItem->{$name}()->pluck('id')->toArray();
            $newList = $this->{$name};
            $newIds = $newList->pluck('id')->filter()->toArray();

            $idsToDelete = array_diff($existingIds, $newIds);
            $this->{$name}()->whereIn('id', $idsToDelete)->delete();

            foreach ($newList->whereIn('id', $existingIds) as $newItem) {
                $updateItem = $this->{$name}()->find($newItem->id);
                $updateItem->fill($newItem);
                $updateItem->save();
            }

            $newOnes = $newList->whereNull('id')
                ->merge($newList->where('id', 0));

            $this->{$name}()->createMany($newOnes->toArray());
        }
    }

    public function syncHasOne($name)
    {
        $relation = $this->{$name}();

        if (!isset($this->id) || $this->id == 0) {
            if ($this->{$name}) {
                $relation->save($this->{$name});
            }
            return;
        }

        $currentItem = self::find($this->id);
        $existingItem = $currentItem->{$name};
        $newItem = $this->{$name};

        if (!$newItem) {
            if ($existingItem) {
                $existingItem->delete();
            }
            return;
        }

        if ($existingItem) {
            if ($newItem->id && $existingItem->id === $newItem->id) {
                $existingItem->fill($newItem->toArray());
                $existingItem->save();
            } else {
                $existingItem->delete();
                $relation->save($newItem);
            }
        } else {
            $relation->save($newItem);
        }
    }

    public function syncBelongsTo($name)
    {
        $relation = $this->{$name}();

        if (!isset($this->id) || $this->id == 0) {
            if ($this->{$name}) {
                $relation->associate($this->{$name});
            }
            return;
        }

        $currentItem = self::find($this->id);
        $existingParent = $currentItem->{$name};
        $newParent = $this->{$name};

        if (!$newParent) {
            if ($existingParent) {
                $relation->dissociate();
                $this->save();
            }
            return;
        }

        if ($existingParent && $newParent->id === $existingParent->id) {
            $existingParent->fill($newParent->toArray());
            $existingParent->save();
        } else {
            if (!$newParent->id) {
                $newParent->save();
            }
            $relation->associate($newParent);
            $this->save();
        }
    }

    public function syncBelongsToMany($name)
    {
        // Exemple : $name = 'roles' dans User->roles()
        $relation = $this->{$name}();

        if (!isset($this->id) || $this->id == 0) {
            $this->save();
        }

        $newList = $this->{$name};

        if (!$newList) {
            $relation->detach();
            return;
        }

        $existingIds = $relation->pluck($relation->getRelated()->getKeyName())->toArray();
        $newIds = $newList->pluck('id')->filter()->toArray();

        $idsToDetach = array_diff($existingIds, $newIds);
        if (count($idsToDetach) > 0) {
            $relation->detach($idsToDetach);
        }

        foreach ($newList->whereIn('id', $existingIds) as $item) {
            if (isset($item->pivot)) {
                $relation->updateExistingPivot($item->id, $item->pivot->toArray());
            }
        }

        $newOnes = $newList->whereNull('id')->merge($newList->where('id', 0));
        foreach ($newOnes as $item) {
            $item->save();
            $relation->attach($item->id, $item->pivot ? $item->pivot->toArray() : []);
        }

        $idsToAttach = array_diff($newIds, $existingIds);
        if (count($idsToAttach) > 0) {
            foreach ($newList->whereIn('id', $idsToAttach) as $item) {
                $relation->attach($item->id, $item->pivot ? $item->pivot->toArray() : []);
            }
        }
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $unfillable = array_diff_key($attributes, array_flip($this->getFillable()));
        $attrs = [];
        foreach ($attributes as $key => $value) {
            if (!array_key_exists($key, $unfillable)) {
                $attrs[$key] = $value;
            }
        }
        parent::fill($attrs);

        if (!$this->only_fillable && count($unfillable) > 0) {
            $deny = $this->preventKeys();
            foreach ($unfillable as $key => $value) {
                if (!$this->isRelation($key)) {
                    if (!in_array($key, $deny)) {
                        $this->{$key} = $value;
                    }
                } else {
                    if (is_array($value)) {
                        $value = new Collection($value);
                    }
                    $this->setRelation($key, $value);
                }
            }
        }
        return $this;
    }

    public function save(array $options = []): bool
    {
        $result = parent::save($options);
        foreach ($this->__links as $name => $type) {
            if ($type == "HasOne") {
                $this->syncHasOne($name);
            } else if ($type == "HasMany") {
                $this->syncHasMany($name);
            } else if ($type == "BelongsTo") {
                $this->syncBelongsTo($name);
            } else if ($type == "BelongsToMany") {
                $this->syncBelongsToMany($name);
            }
        }
        return $result;
    }


    // /**
    //  * Dynamically set attributes on the model.
    //  *
    //  * @param  string  $key
    //  * @param  mixed  $value
    //  * @return void
    //  */
    // public function __set($key, $value)
    // {

    //     parent::__set($key, $value);
    // }
}
