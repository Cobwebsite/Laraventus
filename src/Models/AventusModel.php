<?php

namespace Aventus\Laraventus\Models;

use Aventus\Laraventus\Tools\Console;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

abstract class AventusModel extends Model
{
    public static bool $debug = false;
    /** @var array<string, ModelInfo> */
    private static array $info = [];
    public bool $only_fillable = true;
    public array $saveLinks = [];
    private bool $is_new = false;
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

    protected function __getArray($item): array
    {
        if ($item instanceof Model) {
            return $item->toArray();
        }
        if (is_array($item)) {
            return $item;
        }

        throw new Exception("The $item must be an array or a Model");
    }

    protected function __isNew($item = null, $model = null): bool
    {
        if ($item == null) {
            $item = $this;
        }
        if ($model == null) {
            $model = get_class($this);
        }

        $value = null;
        if ($item instanceof Model) {
            $key = $item->primaryKey;
            $type = $item->getKeyType();
            if (isset($item->{$key})) {
                $value = $item->{$key};
            }
        } else {
            /** @var Model */
            $itemTemp = new $model();
            $key = $itemTemp->primaryKey;
            $type = $itemTemp->getKeyType();
            if (isset($item[$key])) {
                $value = $item[$key];
            }
        }

        if ($value == null) return true;
        if ($type == "int") {
            return $value == 0;
        }
        return $value == '';
    }
    protected function __getPrimary($item = null, $model = null): string|int
    {
        if ($item == null) {
            $item = $this;
        }
        if ($model == null) {
            $model = get_class($this);
        }

        $value = null;
        if ($item instanceof Model) {
            $key = $item->primaryKey;
            if (isset($item->{$key})) {
                $value = $item->{$key};
            }
        } else {
            /** @var Model */
            $itemTemp = new $model();
            $key = $itemTemp->primaryKey;
            if (isset($item[$key])) {
                $value = $item[$key];
            }
        }

        return $value;
    }


    public function syncHasMany($name)
    {

        $currentItem = self::find($this->__getPrimary());
        /** @var HasMany $relation  */
        $relation = $currentItem->{$name}();
        $relationKey = $relation->getRelated()->getKeyName();
        $relationModel = get_class($relation->getRelated());

        $existingIds = $currentItem->{$name}()->pluck($relationKey)->toArray();
        $newList = $this->{$name};
        $newIds = $newList->pluck($relationKey)->filter()->toArray();
        $idsToDelete = array_diff($existingIds, $newIds);
        $this->{$name}()->whereIn($relationKey, $idsToDelete)->delete();

        foreach ($newList->whereIn($relationKey, $existingIds) as $newItem) {
            if (is_object($newItem) && is_a(get_class($newItem), $relationModel, true)) {
                $relation->save($newItem);
            } else {
                $dataArr = [];
                if ($newItem instanceof Model) {
                    $updateItem = $this->{$name}()->find($newItem->{$relationKey});
                    $dataArr = $newItem->toArray();
                } else if (is_object($newItem)) {
                    $updateItem = $this->{$name}()->find($newItem->{$relationKey});
                    $dataArr = get_object_vars($newItem);
                } else {
                    $updateItem = $this->{$name}()->find($newItem[$relationKey]);
                    $dataArr = $newItem;
                }

                $updateItem->fill($dataArr);
                $updateItem->save();
            }
        }

        foreach ($newList as $newItem) {
            if (is_object($newItem) && is_a(get_class($newItem), $relationModel, true)) {
                $relation->save($newItem);
            } else {
                $dataArr = null;
                if ($newItem instanceof Model) {
                    if (!isset($newItem->{$relationKey}) || $newItem->{$relationKey} == 0) {
                        $dataArr = $newItem->toArray();
                    }
                } else if (is_object($newItem)) {
                    if (!isset($newItem->{$relationKey}) || $newItem->{$relationKey} == 0) {
                        $dataArr = get_object_vars($newItem);
                    }
                } else {
                    if (!isset($newItem[$relationKey]) || $newItem[$relationKey] == 0) {
                        $dataArr = $newItem;
                    }
                }
                if ($dataArr != null) {
                    $createItem = new $relationModel($dataArr);
                    $createItem->save();
                }
            }
        }
    }

    public function syncHasOne($name)
    {
        /** @var HasOne $relation  */
        $relation = $this->{$name}();

        $currentItem = self::find($this->__getPrimary());
        $existingItem = $currentItem->{$name};
        $newItem = $this->{$name};
        $relationModel = get_class($relation->getRelated());

        if (!$newItem) {
            if ($existingItem) {
                $existingItem->delete();
            }
            return;
        }

        $relationKey = $relation->getRelated()->getKeyName();

        if ($existingItem) {
            if (is_object($newItem) && is_a(get_class($newItem), $relationModel, true)) {
                $relation->save($newItem);
            } else {
                if ($newItem instanceof Model) {
                    if ($newItem->{$relationKey} && $existingItem->{$relationKey} === $newItem->{$relationKey}) {
                        $existingItem->fill($newItem->toArray());
                        $existingItem->save();
                    } else {
                        $existingItem->delete();
                        $relation->save($newItem);
                    }
                } else if (is_object($newItem)) {
                    if ($newItem->{$relationKey} && $existingItem->{$relationKey} === $newItem->{$relationKey}) {
                        $dataArr = get_object_vars($newItem);
                        $existingItem->fill($dataArr);
                        $existingItem->save();
                    } else {
                        $existingItem->delete();
                        $relation->save($newItem);
                    }
                } else {
                    if ($newItem[$relationKey] && $existingItem[$relationKey] === $newItem[$relationKey]) {
                        $existingItem->fill($newItem);
                        $existingItem->save();
                    } else {
                        $existingItem->delete();
                        $relation->save($newItem);
                    }
                }
            }
        } else {
            $relation->save($newItem);
        }
    }

    public function syncBelongsTo($name)
    {
        /** @var BelongsTo $relation */
        $relation = $this->{$name}();

        if ($this->__isNew()) {
            if ($this->{$name}) {
                $relation->associate($this->{$name});
            }
            return;
        }

        $currentItem = self::find($this->__getPrimary());
        $existingParent = $currentItem->{$name};
        $newParent = $this->{$name};

        if (!$newParent) {
            if ($existingParent) {
                $relation->dissociate();
                $this->save();
            }
            return;
        }

        $relationKey = $relation->getRelated()->getKeyName();
        $relationModel = get_class($relation->getRelated());

        if (is_object($newParent) && is_a(get_class($newParent), $relationModel, true)) {
            $newParent->save();
            $relation->associate($newParent);
        } else if ($newParent instanceof Model) {
            if ($existingParent && $newParent->{$relationKey} === $existingParent->{$relationKey}) {
                $existingParent->fill($newParent->toArray());
                $existingParent->save();
            } else {
                if (!$newParent->{$relationKey}) {
                    $newParent->save();
                }
                $relation->associate($newParent);
                $this->save();
            }
        } else {
            if ($existingParent && $newParent[$relationKey] === $existingParent[$relationKey]) {
                $existingParent->fill($newParent);
                $existingParent->save();
            } else {
                if (!$newParent[$relationKey]) {
                    $newParent->save();
                }
                $relation->associate($newParent);
                $this->save();
            }
        }
    }

    public function syncBelongsToMany($name)
    {
        $relation = $this->{$name}();

        if ($this->__isNew()) {
            $this->save();
        }

        $newList = $this->{$name};

        if (!$newList) {
            $relation->detach();
            return;
        }

        $relationKey = $relation->getRelated()->getKeyName();

        $existingIds = $relation->pluck($relationKey)->toArray();
        $newIds = $newList->pluck($relationKey)->filter()->toArray();

        $idsToDetach = array_diff($existingIds, $newIds);
        if (count($idsToDetach) > 0) {
            $relation->detach($idsToDetach);
        }

        foreach ($newList->whereIn($relationKey, $existingIds) as $item) {
            if (isset($item->pivot)) {
                $relation->updateExistingPivot($item->{$relationKey}, $item->pivot->toArray());
            }
        }

        foreach ($newList as $newItem) {
            if (!isset($newItem->{$relationKey}) || $newItem->{$relationKey} == 0) {
                $newItem->save();
                $relation->attach($newItem->{$relationKey}, $newItem->pivot ? $newItem->pivot->toArray() : []);
            }
        }

        $idsToAttach = array_diff($newIds, $existingIds);
        if (count($idsToAttach) > 0) {
            foreach ($newList->whereIn($relationKey, $idsToAttach) as $item) {
                $relation->attach($item->{$relationKey}, $item->pivot ? $item->pivot->toArray() : []);
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

        $reflection = new ReflectionClass(get_class($this));
        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            if ($this->isRelation($method->name) && isset($attributes[$method->name])) {
                $value = $attributes[$method->name];
                if (is_array($value)) {
                    $value = new Collection($value);
                }
                $this->setRelation($method->name, $value);
            }
        }

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
        if (count($this->saveLinks) > 0) {
            $arr = $this->saveLinks;
            if (array_keys($arr) === range(0, count($arr) - 1)) {
                foreach ($arr as $name) {
                    $reflection = new ReflectionClass(get_class($this));
                    $method = $reflection->getMethod($name);
                    $returnType = $method->getReturnType();
                    if ($returnType instanceof ReflectionNamedType) {
                        if ($returnType->getName() == HasOne::class) {
                            $this->syncHasOne($name);
                        } else if ($returnType->getName() == HasMany::class) {
                            $this->syncHasMany($name);
                        } else if ($returnType->getName() == BelongsTo::class) {
                            $this->syncBelongsTo($name);
                        } else if ($returnType->getName() == BelongsToMany::class) {
                            $this->syncBelongsToMany($name);
                        }
                    }
                }
            } else {
                foreach ($arr as $name => $type) {
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
