<?php
declare(strict_types=1);

namespace Mschindler83\Tests\ArrayAccess;

use Mschindler83\ArrayAccess\ArrayAccessValidationFailed;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class ArrayAccessValidationFailedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_return_errors(): void
    {
        $schema = Schema::fromJsonString(\file_get_contents(__DIR__ . '/../Fixture/json-schema.json'));
        $result = (new Validator())->schemaValidation(\json_decode(\json_encode(['key1' => 'a'])), $schema, 10);
        $errors = $result->getErrors();
        $exception = ArrayAccessValidationFailed::withValidationErrors(...$errors);

        static::assertSame('Json schema validation failed', $exception->getMessage());
        static::assertSame(
            [
                [
                    'required' => [
                        'missing' => 'key2'
                    ],
                ],
                'key1' => [
                    'minLength' => [
                        'min' => 3,
                        'length' => 1,
                    ],
                ],
            ],
            $exception->errorMapping()->data()
         );
    }
}