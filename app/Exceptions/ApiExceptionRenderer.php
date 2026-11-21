<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as PermissionUnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Renders every error on api/* as { "message": ..., "errors": {...} }, with messages in the request locale.
 */
class ApiExceptionRenderer
{
    private const string DEFAULT_AUTHORIZATION_MESSAGE = 'This action is unauthorized.';

    public function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') || $exception instanceof HttpResponseException) {
            return null;
        }

        [$status, $message, $errors, $headers] = match (true) {
            $exception instanceof ValidationException => [$exception->status, $exception->getMessage(), $exception->errors(), []],
            $exception instanceof AuthenticationException => [Response::HTTP_UNAUTHORIZED, __('api.unauthenticated'), [], []],
            $exception instanceof HttpExceptionInterface => [
                $exception->getStatusCode(),
                $this->httpExceptionMessage($exception),
                [],
                $exception->getHeaders(),
            ],
            default => [Response::HTTP_INTERNAL_SERVER_ERROR, __('api.server_error'), [], []],
        };

        $body = [
            'message' => $message,
            'errors' => (object) $errors,
        ];

        if (config('app.debug') && $status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $body['debug'] = [
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->map(fn (array $frame): array => array_diff_key($frame, ['args' => true]))->all(),
            ];
        }

        return new JsonResponse($body, $status, $headers);
    }

    private function httpExceptionMessage(HttpExceptionInterface $exception): string
    {
        $previous = $exception->getPrevious();

        // Framework and package messages are English (and some name classes), so they are replaced by translated ones.
        return match (true) {
            $exception instanceof NotFoundHttpException,
            $previous instanceof ModelNotFoundException,
            $previous instanceof BackedEnumCaseNotFoundException => __('api.not_found'),
            $exception instanceof MethodNotAllowedHttpException => __('api.method_not_allowed'),
            $exception instanceof ThrottleRequestsException => __('api.too_many_requests'),
            $exception instanceof InvalidSignatureException => __('api.invalid_signature'),
            $exception instanceof PermissionUnauthorizedException => __('api.forbidden'),
            $previous instanceof AuthorizationException && $exception->getMessage() === self::DEFAULT_AUTHORIZATION_MESSAGE => __('api.forbidden'),
            $exception->getMessage() === '' => __('api.error'),
            default => $exception->getMessage(),
        };
    }
}
