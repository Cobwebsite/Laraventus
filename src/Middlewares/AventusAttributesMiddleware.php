<?php

namespace Aventus\Laraventus\Middlewares;

use Aventus\Laraventus\Attributes\Middleware;
use Aventus\Laraventus\Helpers\AventusError;
use Aventus\Laraventus\Helpers\LaravelResult;
use Aventus\Laraventus\Resources\AventusResource;
use Aventus\Laraventus\Resources\TextResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Aventus\Laraventus\Tools\Type;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AventusAttributesMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $action = $route->getAction();

        if (isset($action['controller'])) {
            [$controllerClass, $method] = explode('@', $action['controller']);

            $refMethod = new ReflectionMethod($controllerClass, $method);

            foreach ($refMethod->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                if (is_a($instance, Middleware::class, true)) {
                    $middlewares = is_array($instance->middlewares)
                        ? $instance->middlewares
                        : [$instance->middlewares];

                    foreach ($middlewares as $middlewareClass) {
                        $middleware = App::make($middlewareClass);
                        $next = fn($req) => $middleware->handle($req, $next);
                    }
                }
            }
        }

        $response = $next($request);
        return $response;
    }
}
