<?php

namespace Aventus\Laraventus\Requests;

use Aventus\Laraventus\Attributes\Export;

#[Export]
class IdsManyRequest extends AventusRequest
{
    public array $ids = [];
}
