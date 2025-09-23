<?php

namespace Aventus\Laraventus\Exceptions;

use Aventus\Laraventus\Helpers\AventusError;
use Aventus\Laraventus\Helpers\LaravelResult;
use Aventus\Laraventus\Tools\Console;
use Aventus\Laraventus\Tools\Type;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class AventusExceptionCatcher
{
    public static function use(Exceptions $exceptions, ?callable $apply = null)
    {
        $exceptions->render(function (Throwable $e, Request $request) use ($apply) {
            if ($apply != null) {
                if ($apply($e, $request)) {
                    return self::toAventusError($e, $request);
                }
            } else {
                return self::toAventusError($e, $request);
            }
        });
    }

    public static function toAventusError(Throwable $e, Request $request)
    {
        // Console::log($e);
        $errors = [];
        $code = LaraventusErrorEnum::UnknowError->value;
        if ($e instanceof ValidationException) {
            $errors[] = new AventusError(LaraventusErrorEnum::ValidationError, $e->getMessage(), $e->errors());
            $code = LaraventusErrorEnum::ValidationError->value;
        } else if ($e instanceof AuthenticationException) {
            $errors[] = new AventusError(LaraventusErrorEnum::AuthenticationError, $e->getMessage());
            $code = LaraventusErrorEnum::AuthenticationError->value;
        } else {
            if ($e->getCode() != 0) {
                $code = $e->getCode();
            }
            if (!is_int($code)) {
                $code = intval($code);
            }
            $errors[] = new AventusError($code, $e->getMessage(), [
                $e->getFile() . ":" . $e->getLine()
            ]);
        }
        $response = new LaravelResult(null, $errors);
        $response = Type::enrich($response);
        if (!array_key_exists($code, Response::$statusTexts)) {
            $code = 500;
        }

        return response()->json($response, $code);
    }
}
