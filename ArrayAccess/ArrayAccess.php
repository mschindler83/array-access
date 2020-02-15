<?php
declare(strict_types=1);

namespace Mschindler83\ArrayAccess;

use BadMethodCallException;
use Mschindler83\ArrayAccess\DotAnnotation\DotAnnotation;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;

/**
 * @method string string(...$path)
 * @method string|null stringOrNull(...$path)
 * @method int int(...$path)
 * @method int|null intOrNull(...$path)
 * @method float float(...$path)
 * @method float|null floatOrNull(...$path)
 * @method bool bool(...$path)
 * @method bool|null boolOrNull(...$path)
 * @method array array(...$path)
 * @method array|null arrayOrNull(...$path)
 */
class ArrayAccess
{
    private array $data;

    public static function create($value): self
    {
        if (!is_array($value)) {
            throw ArrayAccessFailed::notAnArray($value);
        }

        return new self($value);
    }

    public static function createWithJsonSchemaValidation($value, string $jsonSchemaDefinition): self
    {
        if (!is_array($value)) {
            throw ArrayAccessFailed::notAnArray($value);
        }
        $schema = Schema::fromJsonString($jsonSchemaDefinition);
        $result = (new Validator())->schemaValidation(\json_decode(\json_encode($value)), $schema, 10);

        if (!$result->isValid()) {
            throw ArrayAccessFailed::jsonSchemaValidationFailed(...$result->getErrors());
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
        return self::create($this->array(...$path));
    }

    public function objectOfType(string $objectType, ...$path): object
    {
        $value = $this->findInPath(...$path);

        if (get_class($value) !== $objectType) {
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

    public function callback(callable $callback, ...$path)
    {
        $value = $this->findInPath(...$path);
        if (!$callback($value)) {
            throw ArrayAccessFailed::failedByCallbackRestriction($path);
        }

        return $value;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function __call($name, $arguments)
    {
        $validMethods = [
            'int', 'intOrNull',
            'string', 'stringOrNull',
            'float', 'floatOrNull',
            'bool', 'boolOrNull',
            'array', 'arrayOrNull'
        ];
        if (!in_array($name, $validMethods)) {
            throw new BadMethodCallException(\sprintf('Method "%s" not found', $name));
        }

        $value = $this->findInPath(...$arguments);
        if (substr($name, -6) === 'OrNull' && $value === null) {
            return null;
        }

        $validationFunction = 'is_' . str_replace('OrNull', '', $name);
        if (!call_user_func($validationFunction, $value)) {
            throw ArrayAccessFailed::invalidType($arguments, $value, $name);
        }

        return $value;
    }

    private function findInPath(...$path)
    {
        $temp = &$this->data;
        foreach ($path as $key) {
            if (!is_array($temp) || !array_key_exists($key, $temp)) {
                throw ArrayAccessFailed::pathNotFound();
            }
            $temp = &$temp[$key];
        }

        return $temp;
    }

    private function __construct(array $value)
    {
        $this->data = $value;
    }
}
