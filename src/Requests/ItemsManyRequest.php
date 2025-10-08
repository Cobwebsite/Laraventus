<?php

namespace Aventus\Laraventus\Requests;

use Aventus\Laraventus\Attributes\Export;
use Aventus\Laraventus\Models\AventusModel;

/**
 * @template T of AventusModel = AventusModel
 * @property T[] $items
 */
#[Export]
class ItemsManyRequest extends AventusRequest
{
    public array $items = [];
}
