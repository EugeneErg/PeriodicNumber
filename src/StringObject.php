<?php namespace EugeneEgr\PeriodicNumber;

class StringObject implements \JsonSerializable, \Iterator
{
    /** @var string */
    private $value;
    private $position = 0;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getLength(): int
    {
        return strlen($this->value);
    }

    public function current(): ?self
    {
        return new static($this->value[$this->position]) ?? null;
    }

    public function next(): ?self
    {
        return new static($this->value[++$this->position]) ?? null;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->getLength() > $this->position;
    }

    public function rewind(): ?self
    {
        return new static($this->value[$this->position = 0]) ?? null;
    }

    public function getPosition(self $needle, int $offset = 0): ?int
    {
        $result = strpos($this->value, $needle->value, $offset);

        return $result === false ? null : $result;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __debugInfo(): string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function isEqual(string $string): bool
    {
        return $this->value === $string;
    }
}