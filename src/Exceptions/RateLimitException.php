<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Exceptions;

final class RateLimitException extends InterException
{
    /**
     * @param list<array{razao: string, propriedade: string, valor: string}> $violacoes
     */
    public function __construct(
        string $message,
        int $code = 429,
        array $violacoes = [],
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $code, $violacoes);
    }
}
