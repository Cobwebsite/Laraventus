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

class AventusMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($response instanceof Response) {
            if ($response instanceof JsonResponse) {
                $data = $response->getOriginalContent();
                $code = $response->getStatusCode();
                if ($data instanceof LaravelResult) {
                    $dataEnrich = Type::enrich($data);
                } else if ($data instanceof AventusError) {
                    $code = $data->getHttpCode();
                    $dataEnrich = new LaravelResult(null, [$data]);
                    $dataEnrich = Type::enrich($dataEnrich);
                } else {
                    if ($data instanceof AventusResource) {
                        $data->setUseLastSerialize(true);
                    }
                    $dataEnrich = new LaravelResult($data);
                    $dataEnrich = Type::enrich($dataEnrich);
                }
                $response = new JsonResponse($dataEnrich, $code, $response->headers->all());
                if ($data instanceof AventusResource) {
                    $data->setUseLastSerialize(false);
                }
            } else if ($response instanceof TextResponse) {
                return $response;
            } else if ($response instanceof StreamedResponse) {
                return $response;
            } else if ($response instanceof BinaryFileResponse) {
                return $response;
            } else if ($response instanceof IlluminateResponse) {
                $data = $response->getOriginalContent();
                $code = $response->getStatusCode();
                if ($data instanceof View) {
                    return $response;
                }

                if ($data == null) {
                    $response->setStatusCode(204);
                }

                if ($data instanceof LaravelResult) {
                    $data = Type::enrich($data);
                } else if ($data instanceof AventusError) {
                    $code = $data->getHttpCode();
                    $data = new LaravelResult(null, [$data]);
                    $data = Type::enrich($data);
                } else {
                    $data = new LaravelResult($data);
                    $data = Type::enrich($data);
                }

                $oldHeaders = $response->headers->all();
                unset($oldHeaders['content-type']);
                $response = new JsonResponse($data, $code, $oldHeaders);
            }
        }

        return $response;
    }
}
