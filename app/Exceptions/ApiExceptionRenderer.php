<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders every error on api/* as { "message": ..., "errors": {...} }.
 */
class ApiExceptionRenderer
{
    public function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') || $exception instanceof HttpResponseException) {
            return null;
        }

        [$status, $message, $errors, $headers] = match (true) {
            $exception instanceof ValidationException => [$exception->status, $exception->getMessage(), $exception->errors(), []],
            $exception instanceof AuthenticationException => [Response::HTTP_UNAUTHORIZED, __('Unauthenticated.'), [], []],
            $exception instanceof HttpExceptionInterface => [
                $exception->getStatusCode(),
                $this->httpExceptionMessage($exception),
                [],
                $exception->getHeaders(),
            ],
            default => [Response::HTTP_INTERNAL_SERVER_ERROR, __('Server Error'), [], []],
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
        // Missing-model and missing-enum-case messages name the class, which should not reach clients.
        $previous = $exception->getPrevious();

        if ($previous instanceof ModelNotFoundException || $previous instanceof BackedEnumCaseNotFoundException || $exception->getMessage() === '') {
            return Response::$statusTexts[$exception->getStatusCode()] ?? __('Server Error');
        }

        return $exception->getMessage();
    }
}
