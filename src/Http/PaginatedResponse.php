<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
final readonly class PaginatedResponse implements Countable, IteratorAggregate
{
    /**
     * @param list<array<string, mixed>> $data
     */
    public function __construct(
        public array $data,
        public int $totalPages,
        public int $totalElements,
        public int $numberOfElements,
        public bool $last,
        public bool $first,
        public int $size,
    ) {}

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return Traversable<int, array<string, mixed>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }
}
