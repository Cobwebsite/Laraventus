<?php

namespace Aventus\Laraventus\Attributes;

use Attribute;

#[Attribute]
class DefaultValueRaw {
    public function __construct(public string $value)
    {
        
    }
}