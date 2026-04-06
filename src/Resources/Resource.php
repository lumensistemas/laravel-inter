<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Resources;

use LumenSistemas\Inter\Contracts\InterClientInterface;

abstract class Resource
{
    public function __construct(
        protected readonly InterClientInterface $client,
    ) {}

    abstract protected function resourcePath(): string;

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function filterNulls(array $data): array
    {
        return array_filter($data, static fn (mixed $value): bool => $value !== null);
    }
}
