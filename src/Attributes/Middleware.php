<?php

namespace Aventus\Laraventus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(
        public array|string $middlewares
    ) {}
}