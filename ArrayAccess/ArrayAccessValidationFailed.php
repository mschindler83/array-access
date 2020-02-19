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
        $instance = new self('Json schema validation failed');
        $instance->errors = $errors;
        $instance->errorMapping = ArrayAccess::create([]);

        foreach ($errors as $error) {
            $instance->errorMapping = $instance->errorMapping->writeAtPath([$error->keyword() => $error->keywordArgs()], ...$error->dataPointer());
        }

        return $instance;
    }

    public function errorMapping(): ArrayAccess
    {
        return $this->errorMapping;
    }
}