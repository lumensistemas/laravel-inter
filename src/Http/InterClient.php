<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use LumenSistemas\Inter\Contracts\InterClientInterface;
use LumenSistemas\Inter\Enums\Environment;
use LumenSistemas\Inter\Exceptions\AuthenticationException;
use LumenSistemas\Inter\Exceptions\ForbiddenException;
use LumenSistemas\Inter\Exceptions\InterException;
use LumenSistemas\Inter\Exceptions\NotFoundException;
use LumenSistemas\Inter\Exceptions\RateLimitException;
use LumenSistemas\Inter\Exceptions\ServerException;
use LumenSistemas\Inter\Exceptions\ValidationException;
use Throwable;

readonly class InterClient implements InterClientInterface
{
    public function __construct(
        private TokenManager $tokenManager,
        private Environment $environment,
        private string $certificate,
        private string $privateKey,
        private int $timeout,
        private int $retry,
        private string $userAgent,
        private string $contaCorrente = '',
    ) {}

    public function get(string $path, array $query = []): Response
    {
        $response = $this->request()->get($path, $query);

        return new Response($this->handleResponse($response));
    }

    public function post(string $path, array $data = []): Response
    {
        $response = $this->request()->post($path, $data);

        return new Response($this->handleResponse($response));
    }

    public function put(string $path, array $data = []): Response
    {
        $response = $this->request()->put($path, $data);

        return new Response($this->handleResponse($response));
    }

    public function patch(string $path, array $data = []): Response
    {
        $response = $this->request()->patch($path, $data);

        return new Response($this->handleResponse($response));
    }

    public function delete(string $path, array $query = []): Response
    {
        $response = $this->request()->delete($path, $query);

        return new Response($this->handleResponse($response));
    }

    public function list(string $path, string $collectionKey, array $query = []): PaginatedResponse
    {
        $response = $this->request()->get($path, $query);
        $data = $this->handleResponse($response);

        /** @var list<array<string, mixed>> $items */
        $items = $data[$collectionKey] ?? [];

        /** @var int $totalPaginas */
        $totalPaginas = $data['totalPaginas'] ?? 1;
        /** @var int $totalElementos */
        $totalElementos = $data['totalElementos'] ?? 0;
        /** @var int $numeroDeElementos */
        $numeroDeElementos = $data['numeroDeElementos'] ?? 0;
        /** @var bool $ultimaPagina */
        $ultimaPagina = $data['ultimaPagina'] ?? true;
        /** @var bool $primeiraPagina */
        $primeiraPagina = $data['primeiraPagina'] ?? true;
        /** @var int $tamanhoPagina */
        $tamanhoPagina = $data['tamanhoPagina'] ?? 0;

        return new PaginatedResponse(
            data: $items,
            totalPaginas: $totalPaginas,
            totalElementos: $totalElementos,
            numeroDeElementos: $numeroDeElementos,
            ultimaPagina: $ultimaPagina,
            primeiraPagina: $primeiraPagina,
            tamanhoPagina: $tamanhoPagina,
        );
    }

    /**
     * Request builder with common configuration for all requests, including
     * authentication, headers, and retry logic.
     *
     * The retry logic will retry on any 5xx server error, with a delay of
     * 100ms between attempts.
     */
    private function request(): PendingRequest
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->tokenManager->getToken(),
            'User-Agent' => $this->userAgent,
        ];

        if ($this->contaCorrente !== '') {
            $headers['x-conta-corrente'] = $this->contaCorrente;
        }

        return Http::baseUrl($this->environment->baseUrl())
            ->withHeaders($headers)
            ->withOptions([
                'cert' => $this->certificate,
                'ssl_key' => $this->privateKey,
            ])
            ->timeout($this->timeout)
            ->retry($this->retry, 100, fn (?Throwable $exception, PendingRequest $request): bool => $exception instanceof \Illuminate\Http\Client\RequestException
                && $exception->response->status() >= 500, throw: false)
            ->acceptJson()
            ->asJson();
    }

    /**
     * @return array<string, mixed>
     */
    private function handleResponse(HttpResponse $response): array
    {
        if ($response->successful()) {
            /** @var array<string, mixed> $data */
            $data = $response->json() ?? [];

            return $data;
        }

        $status = $response->status();

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        /** @var string $message */
        $message = $body['detail'] ?? $body['title'] ?? $body['message'] ?? 'Unknown error';

        /** @var list<array{razao: string, propriedade: string, valor: string}> $violacoes */
        $violacoes = $body['violacoes'] ?? [];

        if ($status === 401) {
            $this->tokenManager->invalidate();
        }

        $retryAfter = $response->header('Retry-After');

        throw match (true) {
            $status === 400 => new ValidationException($message, $status, $violacoes),
            $status === 401 => new AuthenticationException($message, $status, $violacoes),
            $status === 403 => new ForbiddenException($message, $status, $violacoes),
            $status === 404 => new NotFoundException($message, $status, $violacoes),
            $status === 429 => new RateLimitException(
                message: $message,
                code: $status,
                violacoes: $violacoes,
                retryAfter: $retryAfter !== '' ? (int) $retryAfter : null,
            ),
            $status >= 500 => new ServerException($message, $status, $violacoes),
            default => new InterException($message, $status, $violacoes),
        };
    }
}
