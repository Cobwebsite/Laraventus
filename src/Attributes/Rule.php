<?php

namespace Aventus\Laraventus\Attributes;

use Attribute;

#[Attribute]
class Rule
{
    public function __construct(
        public string $rule
    ) {}
}
