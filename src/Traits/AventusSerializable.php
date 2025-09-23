<?php

namespace Aventus\Laraventus\Traits;

use Aventus\Laraventus\Tools\Json;

trait AventusSerializable
{
    public function jsonSerialize(): mixed
    {
        return Json::toJsonObj($this);
    }
}
