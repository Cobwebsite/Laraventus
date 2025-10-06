<?php

namespace Aventus\Laraventus\Controllers;

use Aventus\Laraventus\Models\AventusModel;
use Aventus\Laraventus\Requests\AventusRequest;
use Aventus\Laraventus\Requests\IdsManyRequest;
use Aventus\Laraventus\Requests\ItemsManyRequest;
use Aventus\Laraventus\Resources\AventusModelResource;

/**
 * @template T of AventusModel
 * @template U of AventusRequest
 * @template R of AventusModelResource<T>
 */
abstract class ModelController
{
    public function overrideUri(string $uri)
    {
        $array = explode("/", $uri);
        $replace = null;
        $newUri = ["\${this.getUri()}"];
        $replacePart = [];
        foreach ($array as $part) {
            if (str_starts_with($part, "{")) {
                $newUri[] = $part;
            } else {
                $replacePart[] = $part;
            }
        }
        if (count($replacePart) > 0) {
            $replace = implode("/", $replacePart);
        }
        return [implode("/", $array), $replace];
    }
    /**
     * @return class-string<T>
     */
    public abstract function defineModel(): string;
    /**
     * @return class-string<U>
     */
    public abstract function defineRequest(): string;

    /**
     * @return class-string<R>
     */
    public abstract function defineResource(): string;

    /**
     * Display a listing of the resource.
     * @return R[]
     */
    public function index(): array
    {
        return $this->defineResource()::collection($this->defineModel()::all());
    }

    /**
     * Store a newly created resource in storage.
     * @return R
     */
    public function store(): AventusModelResource
    {
        /** @var AventusRequest $request */
        $request = app($this->defineRequest());
        $item = $request->toModel($this->defineModel());
        $item->save();
        return $this->show($item->id);
    }

    /**
     * Store a newly created resource in storage.
     * @return R[]
     */
    public function storeMany(ItemsManyRequest $request): array
    {
        $model = $this->defineModel();
        $ids = [];
        foreach ($request->items as $data) {
            $item = new $model($data);
            $item->save();
            $ids[] = $item->id;
        }
        return $this->showMany($ids);
    }

    /**
     * Display the specified resource.
     * @return R
     */
    public function show(int $id): AventusModelResource
    {
        $res = $this->defineResource();
        return new $res($this->defineModel()::find($id));
    }

    /**
     * Display the specified resource.
     * @return R[]
     */
    public function showMany(IdsManyRequest|array $request): array
    {
        if ($request instanceof IdsManyRequest) {
            $ids = $request->ids;
        }
        else {
            $ids = $request;
        }
        return $this->defineResource()::collection($this->defineModel()::whereIn("id", $ids)->get());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id): AventusModelResource
    {
        /** @var AventusRequest $request */
        $request = app($this->defineRequest());
        $item = $request->toModel($this->defineModel());
        $item->id = $id;
        $item->exists = true;
        $item->save();
        return $this->show($item->id);
    }

    /**
     * Update the specified resource in storage.
     * @return R[]
     */
    public function updateMany(ItemsManyRequest $request): array
    {
        $model = $this->defineModel();
        $ids = [];
        foreach ($request->items as $data) {
            $item = new $model($data);
            $item->exists = true;
            $item->save();
            $ids[] = $item->id;
        }
        return $this->showMany($ids);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): bool
    {
        return $this->defineModel()::where('id', $id)->delete();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyMany(IdsManyRequest $request): bool
    {
        return $this->defineModel()::whereIn('id', $request->ids)->delete();
    }
}
