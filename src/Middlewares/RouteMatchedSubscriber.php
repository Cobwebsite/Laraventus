<?php


namespace Aventus\Laraventus\Middlewares;

use Aventus\Laraventus\Attributes\Middleware;
use Aventus\Laraventus\Tools\Console;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Events\RouteMatched;
use ReflectionClass;
use ReflectionMethod;

class RouteMatchedSubscriber
{
    public function handleRouteMatch(RouteMatched $event): void
    {
        $route = $event->route;
        $action = $route->getAction();

        if (isset($action['controller'])) {
            [$controllerClass, $method] = explode('@', $action['controller']);

            $middlewares = [];

            $refClass = new ReflectionClass($controllerClass);
            foreach ($refClass->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                if (is_a($instance, Middleware::class, true)) {
                    $middlewaresTemp = is_array($instance->middlewares)
                        ? $instance->middlewares
                        : [$instance->middlewares];

                    $middlewares = array_merge(
                        $middlewares,
                        $middlewaresTemp
                    );
                }
            }

            $refMethod = new ReflectionMethod($controllerClass, $method);
            foreach ($refMethod->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                if (is_a($instance, Middleware::class, true)) {
                    $middlewaresTemp = is_array($instance->middlewares)
                        ? $instance->middlewares
                        : [$instance->middlewares];

                    $middlewares = array_merge(
                        $middlewares,
                        $middlewaresTemp
                    );
                }
            }

            foreach ($middlewares as $middlewareClass) {
                $route->middleware($middlewareClass);
            }
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            RouteMatched::class => 'handleRouteMatch',
        ];
    }
}
