<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Contracts;

use LumenSistemas\Inter\Http\PaginatedResponse;
use LumenSistemas\Inter\Http\Response;

interface InterClientInterface
{
    /**
     * @param array<string, mixed> $query
     */
    public function get(string $path, array $query = []): Response;

    /**
     * @param array<string, mixed> $data
     */
    public function post(string $path, array $data = []): Response;

    /**
     * @param array<string, mixed> $data
     */
    public function put(string $path, array $data = []): Response;

    /**
     * @param array<string, mixed> $data
     */
    public function patch(string $path, array $data = []): Response;

    /**
     * @param array<string, mixed> $query
     */
    public function delete(string $path, array $query = []): Response;

    /**
     * @param array<string, mixed> $query
     */
    public function list(string $path, string $collectionKey, array $query = []): PaginatedResponse;
}
