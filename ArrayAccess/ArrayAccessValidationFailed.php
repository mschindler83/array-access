<?php
declare(strict_types=1);

namespace Mschindler83\ArrayAccess;

use Opis\JsonSchema\ValidationError;

class ArrayAccessValidationFailed extends \RuntimeException
{
    private array $errors;

    private ArrayAccess $errorMapping;

    public static function withValidationErrors(ValidationError ...$errors): self
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

        $instance = new self(
            \sprintf('Json schema validation failed: %s', \implode(', ', $messages))
        );

        $instance->errors = $errors;
        $instance->errorMapping = ArrayAccess::create([]);
        foreach ($errors as $error) {
            $instance->errorMapping = $instance->errorMapping->writeAtPath((string) $error->keyword(), ...$error->dataPointer());
        }

        return $instance;
    }

    public function errorMapping(): ArrayAccess
    {
        return $this->errorMapping;
    }
}