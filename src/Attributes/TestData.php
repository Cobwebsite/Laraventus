<?php

namespace Aventus\Laraventus\Attributes;

use Attribute;

#[Attribute]
class TestData
{
    public function __construct(
        public $value
    ) {}
}
