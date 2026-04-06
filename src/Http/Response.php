<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Http;

final readonly class Response
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public array $data,
    ) {}
}
