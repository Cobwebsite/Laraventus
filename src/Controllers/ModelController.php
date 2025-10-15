<?php

namespace Aventus\Laraventus\Controllers;

use Aventus\Laraventus\Models\AventusModel;
use Aventus\Laraventus\Requests\AventusRequest;
use Aventus\Laraventus\Requests\IdsManyRequest;
use Aventus\Laraventus\Requests\ItemsManyRequest;
use Aventus\Laraventus\Resources\AventusModelResource;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * @template T of AventusModel
 * @template U of AventusRequest
 * @template R of AventusModelResource<T>
 * @template S of R = R
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
     * @return class-string<S>
     */
    public function defineResourceDetails(): string
    {
        return $this->defineResource();
    }

    /**
     * Display a listing of the resource.
     * @return R[]
     */
    public function index(): array
    {
        return $this->defineResource()::collection($this->indexAction());
    }

    /**
     * @return T[]|Collection<int|string, T>|SupportCollection<int|string, T>
     */
    protected function indexAction(): array|Collection|SupportCollection
    {
        return $this->defineModel()::all();
    }

    /**
     * Store a newly created resource in storage.
     * @return S
     */
    public function store(): AventusModelResource
    {
        /** @var AventusRequest $request */
        $request = app($this->defineRequest());
        $item = $request->toModel($this->defineModel());
        try {
            DB::beginTransaction();
            $this->storeAction($item);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
        return $this->show($item->id);
    }

    /**
     * @param T $item
     */
    protected function storeAction($item): void
    {
        $item->save();
    }

    /**
     * Store a newly created resource in storage.
     * @return S[]
     */
    public function storeMany(ItemsManyRequest $request): array
    {
        $model = $this->defineModel();
        $ids = [];
        DB::beginTransaction();
        try {
            foreach ($request->items as $data) {
                $item = new $model($data);
                $this->storeAction($item);
                $ids[] = $item->id;
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
        $r = new IdsManyRequest($request);
        $r->ids = $ids;
        return $this->showMany($r);
    }

    /**
     * Display the specified resource.
     * @return S
     */
    public function show(int|string $id): AventusModelResource
    {
        $res = $this->defineResourceDetails();
        return new $res($this->showAction($id));
    }

    /**
     * @return T
     */
    protected function showAction(int|string $id): AventusModelResource
    {
        return $this->defineModel()::find($id);
    }

    /**
     * Display the specified resource.
     * @return S[]
     */
    public function showMany(IdsManyRequest $request): array
    {
        return $this->defineResourceDetails()::collection($this->showManyAction($request->ids));
    }

    /**
     * @return T[]|Collection<int|string, T>|SupportCollection<int|string, T>
     */
    protected function showManyAction(array $ids): array|Collection|SupportCollection
    {
        return $this->defineModel()::whereIn("id", $ids)->get();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int|string $id): AventusModelResource
    {
        /** @var AventusRequest $request */
        $request = app($this->defineRequest());
        $item = $request->toModel($this->defineModel());
        $item->id = $id;
        $item->exists = true;
        try {
            DB::beginTransaction();
            $this->updateAction($item);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
        return $this->show($item->id);
    }

    /**
     * @param T $item
     */
    protected function updateAction($item): void
    {
        $item->save();
    }

    /**
     * Update the specified resource in storage.
     * @return S[]
     */
    public function updateMany(ItemsManyRequest $request): array
    {
        $model = $this->defineModel();
        $ids = [];
        DB::beginTransaction();
        try {
            foreach ($request->items as $data) {
                $item = new $model($data);
                $item->exists = true;
                $this->updateAction($item);
                $ids[] = $item->id;
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
        $r = new IdsManyRequest($request);
        $r->ids = $ids;
        return $this->showMany($r);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int|string $id): bool
    {
        DB::beginTransaction();
        try {
            return $this->destroyAction($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return false;
    }

    /**
     * Remove the specified resource from storage.
     */
    protected function destroyAction(int|string $id): bool
    {
        return $this->defineModel()::where('id', $id)->delete();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyMany(IdsManyRequest $request): bool
    {
        DB::beginTransaction();
        try {
            return $this->destroyManyAction($request->ids);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return false;
    }

    /**
     * Remove the specified resource from storage.
     */
    protected function destroyManyAction(array $ids): bool
    {
        return $this->defineModel()::whereIn('id', $ids)->delete();
    }
}
