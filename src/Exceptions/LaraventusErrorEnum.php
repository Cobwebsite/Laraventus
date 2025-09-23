<?php

namespace Aventus\Laraventus\Exceptions;

use Aventus\Laraventus\Attributes\Export;

#[Export]
enum LaraventusErrorEnum: int
{
    case AuthenticationError = 401;
    case ValidationError = 422;
    case UnknowError = 500;
}
