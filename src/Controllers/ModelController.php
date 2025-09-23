<?php

namespace Aventus\Laraventus\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Aventus\Laraventus\Models\AventusModel;
use Aventus\Laraventus\Requests\AventusRequest;
use Aventus\Laraventus\Resources\AventusModelResource;
use Aventus\Laraventus\Tools\Console;

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
     * Display the specified resource.
     * @return R
     */
    public function show(int $id): AventusModelResource
    {
        $res = $this->defineResource();
        return new $res($this->defineModel()::find($id));
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
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): bool
    {
        return $this->defineModel()::where('id', $id)->delete();
    }
}
