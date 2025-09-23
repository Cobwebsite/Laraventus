<?php

namespace Aventus\Laraventus\Attributes;

use Attribute;

#[Attribute]
class Rename
{
    public function __construct(
        public string $name
    ) {}
}
