<?php

namespace Mschindler83\ArrayAccess;

class ArrayAccessFailed extends \RuntimeException
{
    public static function notAnArray($value): self
    {
        return new self(\sprintf('Given parameter "%s" is not an array', gettype($value)));
    }

    public static function invalidType(array $arrayPath, $value, string $expectedType): self
    {
        return new self(
            \sprintf(
                '[Array path: %s] Could not get value for "%s". Invalid type "%s". Expected type: "%s"',
                \implode('.', $arrayPath),
                end($arrayPath),
                gettype($value),
                $expectedType
            )
        );
    }

    public static function invalidDateTimeType(array $arrayPath, $value, string $dateFormat): self
    {
        return new self(
            \sprintf(
                '[Array path: %s] Could not get datetime object at "%s" with format "%s" from value "%s"',
                \implode('.', $arrayPath),
                end($arrayPath),
                $dateFormat,
                $value
            )
        );
    }

    public static function pathNotFound(): self
    {
        return new self('Path not found');
    }
}