<?php
declare(strict_types=1);

namespace Mschindler83\Tests\ArrayAccess;

use Mschindler83\ArrayAccess\ArrayAccessValidationFailed;
use Opis\JsonSchema\ValidationError;
use PHPUnit\Framework\TestCase;

class ArrayAccessValidationFailedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_return_errors(): void
    {
        $error1 = new ValidationError(null, ['e1dp1', 'e1dp2'], [], false, 'kw1');
        $error2 = new ValidationError(null, ['e2dp1', 'e2dp2'], [], false, 'kw2');

        $exception = ArrayAccessValidationFailed::withValidationErrors($error1, $error2);

        static::assertSame(
            [
                'e1dp1' => [
                    'e1dp2' => 'kw1'
                ],
                'e2dp1' => [
                    'e2dp2' => 'kw2'
                ],
            ],
            $exception->errorMapping()->data()
         );
    }
}