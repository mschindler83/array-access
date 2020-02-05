<?php

namespace Mschindler83\ArrayAccess;

use BadMethodCallException;
use Mschindler83\ArrayAccess\DotAnnotation\DotAnnotation;

/**
 * @method string string(...$path)
 * @method int int(...$path)
 * @method float float(...$path)
 * @method bool bool(...$path)
 * @method array array(...$path)
 */
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

    public static function newFromDotAnnotation(DotAnnotation ...$dotAnnotations): self
    {
        $newArray = [];

        foreach ($dotAnnotations as $dotAnnotation) {
            $pointer = &$newArray;
            foreach ($dotAnnotation->path() as $key) {
                $pointer = &$pointer[$key];
            }
            $pointer = $dotAnnotation->value();
        }

        return new self($newArray);
    }

    public function writeAtPath($value, ...$path): self
    {
        $pointer = &$this->data;
        foreach ($path as $key) {
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

    public function dateTime(string $format, ...$path): \DateTime
    {
        $value = $this->string(...$path);

        $dateTime = \DateTime::createFromFormat($format, $value);
        if ($dateTime === false) {
            throw ArrayAccessFailed::invalidDateTimeType($path, $value, $format);
        }

        return $dateTime;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function __call($name, $arguments)
    {
        if (!in_array($name, ['int', 'string', 'float', 'bool', 'array'])) {
            throw new BadMethodCallException(\sprintf('Method "%s" not found', $name));
        }

        $value = $this->findInPath(...$arguments);
        if (!call_user_func('is_' . $name, $value)) {
            throw ArrayAccessFailed::invalidType($arguments, $value, $name);
        }

        return $value;
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
