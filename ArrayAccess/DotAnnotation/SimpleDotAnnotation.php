<?php
declare(strict_types=1);

namespace Mschindler83\ArrayAccess\DotAnnotation;

class SimpleDotAnnotation implements DotAnnotation
{
    private array $path = [];

    /**
     * @var mixed
     */
    private $value;

    public static function create(string $path, $value): self
    {
        return new self($path, $value);
    }

    public function path(): array
    {
        return $this->path;
    }

    public function value()
    {
        return $this->value;
    }

    private function normalizePathCallback()
    {
        return function (string $pathElement) {
            if (trim((string) $pathElement) === '') {
                return 0;
            }

            return $pathElement;
        };
    }

    private function __construct(string $path, $value)
    {
        $this->path = \array_map($this->normalizePathCallback(), \explode('.', $path));
        $this->value = $value;
    }
}
