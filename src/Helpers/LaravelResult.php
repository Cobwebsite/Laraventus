<?php

namespace Aventus\Laraventus\Helpers;

use Aventus\Laraventus\Traits\AventusSerializable;
use JsonSerializable;

/**
 * @template T 
 * @property T|null $data
 * @property AventusError[] $errors
 */
class LaravelResult implements JsonSerializable
{
    use AventusSerializable;

    public function __construct(
        public $result,
        public array $errors = [],
    ) {}
}
