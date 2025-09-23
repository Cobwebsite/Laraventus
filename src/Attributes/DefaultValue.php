<?php

namespace Aventus\Laraventus\Attributes;

use Attribute;

#[Attribute]
class DefaultValue {
    public function __construct(public $value)
    {
        
    }
}