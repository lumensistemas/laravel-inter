<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Concerns;

use Generator;

trait HasPagination
{
    /**
     * @param array<string, mixed> $query
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function all(array $query = [], int $itensPorPagina = 100): Generator
    {
        $pagina = 0;

        do {
            $response = $this->client->list($this->resourcePath(), $this->collectionKey(), [
                ...$query,
                'itensPorPagina' => $itensPorPagina,
                'paginaAtual' => $pagina,
            ]);

            foreach ($response->data as $item) {
                yield $item;
            }

            ++$pagina;
        } while (!$response->ultimaPagina);
    }

    abstract protected function resourcePath(): string;

    abstract protected function collectionKey(): string;
}
