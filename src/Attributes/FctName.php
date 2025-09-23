<?php

namespace Aventus\Laraventus\Attributes;

use Attribute;

#[Attribute]
class FctName {
    public function __construct(public string $name)
    {
        
    }
}