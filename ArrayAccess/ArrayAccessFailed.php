<?php
declare(strict_types=1);

namespace Mschindler83\ArrayAccess;

use Opis\JsonSchema\ValidationError;

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
                is_object($value) ? get_class($value) : gettype($value),
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

    public static function failedByCallbackRestriction(array $arrayPath): self
    {
        return new self(
            \sprintf(
                '[Array path: %s] Could not get value for "%s". Reason: Callback restriction',
                \implode('.', $arrayPath),
                end($arrayPath),
            )
        );
    }

    public static function pathNotFound(): self
    {
        return new self('Path not found');
    }

    public static function jsonSchemaValidationFailed(ValidationError ...$errors): self
    {
        $messages = \array_map(
            function(ValidationError $error) {
                return \sprintf(
                    'Error: [%s], Data pointer: [%s]',
                    $error->keyword(),
                    \implode(', ', $error->dataPointer()),
                );
            },
            $errors
        );

        return new self(
            \sprintf('Json schema validation failed: %s', \implode(', ', $messages))
        );
    }
}
