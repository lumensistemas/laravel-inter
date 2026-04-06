<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Exceptions;

use Exception;

class InterException extends Exception
{
    /**
     * @param list<array{razao: string, propriedade: string, valor: string}> $violacoes
     */
    public function __construct(
        string $message,
        int $code = 0,
        public readonly array $violacoes = [],
    ) {
        parent::__construct($message, $code);
    }
}
