<?php

namespace Mschindler83\ArrayAccess;

class ArrayAccess
{
    private $data;

    public static function create($value): self
    {
        if (!is_array($value)) {
            throw ArrayAccessFailed::notAnArray($value);
        }

        return new self($value);
    }

    public static function newFromDotAnnotation(string $dotAnnotation, $value): self
    {
        $dotExploded = \explode('.', $dotAnnotation);
        $newArray = [];
        $pointer = &$newArray;
        foreach ($dotExploded as $key) {
            $pointer[$key] = [];
            $pointer = &$pointer[$key];
        }
        $pointer = $value;

        return new self($newArray);
    }

    public function writeAtPath($value, ...$path): self
    {
        $pointer = &$this->data;
        foreach ($path as $key) {
            if (!isset($pointer[$key])) {
                $pointer[$key] = [];
            }
            $pointer = &$pointer[$key];
        }
        $pointer = $value;

        return new self($this->data);
    }

    public function hasPath(...$path): bool
    {
        try {
            $this->findInPath(...$path);

            return true;
        } catch (ArrayAccessFailed $e) {
            return false;
        }
    }

    public function string(...$path): string
    {
        $value = $this->findInPath(...$path);

        if (!is_string($value)) {
            throw ArrayAccessFailed::invalidType($path, $value, 'string');
        }

        return $value;
    }

    public function integer(...$path): int
    {
        $value = $this->findInPath(...$path);

        if (!is_int($value)) {
            throw ArrayAccessFailed::invalidType($path, $value, 'integer');
        }

        return $value;
    }

    public function float(...$path): float
    {
        $value = $this->findInPath(...$path);

        if (!is_float($value)) {
            throw ArrayAccessFailed::invalidType($path, $value, 'float');
        }

        return $value;
    }

    public function bool(...$path): bool
    {
        $value = $this->findInPath(...$path);

        if (!is_bool($value)) {
            throw ArrayAccessFailed::invalidType($path, $value, 'boolean');
        }

        return $value;
    }

    public function array(...$path): array
    {
        $value = $this->findInPath(...$path);

        if (!is_array($value)) {
            throw ArrayAccessFailed::invalidType($path, $value, 'array');
        }

        return $value;
    }

    public function arrayAccess(...$path): self
    {
        $value = $this->findInPath(...$path);

        if (!is_array($value)) {
            throw ArrayAccessFailed::invalidType($path, $value, 'array');
        }

        return self::create($value);
    }

    public function objectOfType(string $objectType, ...$path): object
    {
        $value = $this->findInPath(...$path);

        if (!($value instanceof $objectType)) {
            throw ArrayAccessFailed::invalidType($path, $value, $objectType);
        }

        return $value;
    }

    public function dateTimeImmutable(string $format, ...$path): \DateTimeImmutable
    {
        $value = $this->string(...$path);

        $dateTime = \DateTimeImmutable::createFromFormat($format, $value);
        if ($dateTime === false) {
            throw ArrayAccessFailed::invalidDateTimeType($path, $value, $format);
        }

        return $dateTime;
    }

    public function data(): array
    {
        return $this->data;
    }

    private function findInPath(...$path)
    {
        $temp = &$this->data;
        $pathCount = \count($path);

        $pos = 0;
        foreach ($path as $key) {
            if (!isset($temp[$key])) {
                throw ArrayAccessFailed::pathNotFound();
            }
            $temp = &$temp[$key];
            if ($temp === null && $pos !== $pathCount - 1) {
                throw ArrayAccessFailed::pathNotFound();
            }
            ++$pos;
        }

        return $temp;
    }

    private function __construct(array $value)
    {
        $this->data = $value;
    }
}
