<?php

namespace Aventus\Laraventus\Attributes;

use Attribute;

#[Attribute]
class ArrayOf
{
    public function __construct(public string $class) {}
}
