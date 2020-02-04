<?php

namespace Tests\mschindler83\ArrayAccess;

use mschindler83\ArrayAccess\ArrayAccess;
use mschindler83\ArrayAccess\ArrayAccessFailed;
use PHPUnit\Framework\TestCase;

class ArrayAccessTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_array_data(): void
    {
        $testArray = [
             'Foo' => [
                 'Bar' => [
                     'Baz' => 'Buz',
                 ],
             ],
         ];
        $access = ArrayAccess::create($testArray);

        static::assertSame($testArray, $access->data());
    }

    /**
     * @test
     */
    public function it_can_be_created_from_dot_annotation(): void
    {
        $access = ArrayAccess::newFromDotAnnotation('key1.key2.2.key3', 'the-value');

        static::assertSame(
            [
             'key1' => [
                 'key2' => [
                     2 => [
                         'key3' => 'the-value',
                     ],
                 ],
             ],
            ],
            $access->data()
        );
    }

    /**
     * @test
     */
    public function it_can_write_at_path(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => 'Buz',
                ],
            ],
        ];

        $access = ArrayAccess::create($testArray);
        $newAccess = $access->writeAtPath('new-value', 'Foo', 'Bar', 'New');

        static::assertSame(
            [
                'Foo' => [
                    'Bar' => [
                        'Baz' => 'Buz',
                        'New' => 'new-value',
                    ],
                ],
            ],
            $newAccess->data()
        );
    }

    /**
     * @test
     */
    public function it_can_overwrite_at_path(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => 'Buz',
                ],
            ],
        ];

        $access = ArrayAccess::create($testArray);
        $newAccess = $access->writeAtPath('new-value', 'Foo', 'Bar', 'Baz');

        static::assertSame(
            [
                'Foo' => [
                    'Bar' => [
                        'Baz' => 'new-value',
                    ],
                ],
            ],
            $newAccess->data()
        );
    }

    /**
     * @test
     */
    public function it_can_return_if_a_path_exists(): void
    {
        $testArray = [
             'Foo' => [
                 'Bar' => [
                     'Baz' => 'Buz',
                 ],
             ],
         ];

        $access = ArrayAccess::create($testArray);

        static::assertTrue($access->hasPath('Foo'));
        static::assertTrue($access->hasPath('Foo', 'Bar'));
        static::assertTrue($access->hasPath('Foo', 'Bar', 'Baz'));
        static::assertFalse($access->hasPath('Foo', 'Bar', 'Baz', 'Buz'));
        static::assertFalse($access->hasPath('BuzBuz'));
    }

    /**
     * @test
     */
    public function it_works_with_strings(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => 'Buz',
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result = $access->string('Foo', 'Bar', 'Baz');

        static::assertSame('Buz', $result);
    }

    /**
     * @test
     */
    public function it_works_with_integers(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => -999,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result = $access->integer('Foo', 'Bar', 'Baz');

        static::assertSame(-999, $result);
    }

    /**
     * @test
     */
    public function it_works_with_floats(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => -98.112,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result = $access->float('Foo', 'Bar', 'Baz');

        static::assertSame(-98.112, $result);
    }

    /**
     * @test
     */
    public function it_works_with_booleans(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'is_true' => true,
                    'is_false' => false,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result1 = $access->bool('Foo', 'Bar', 'is_true');
        $result2 = $access->bool('Foo', 'Bar', 'is_false');

        static::assertTrue($result1);
        static::assertFalse($result2);
    }

    /**
     * @test
     */
    public function it_works_with_arrays(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => -999,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result1 = $access->array('Foo');
        $result2 = $access->array('Foo', 'Bar');

        static::assertSame(['Bar' => ['Baz' => -999]], $result1);
        static::assertSame(['Baz' => -999], $result2);
    }

    /**
     * @test
     */
    public function it_works_with_arrays_returning_new_array_access(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => -999,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result1 = $access->arrayAccess('Foo');
        $result2 = $access->arrayAccess('Foo', 'Bar');

        static::assertSame(['Bar' => ['Baz' => -999]], $result1->data());
        static::assertSame(['Baz' => -999], $result2->data());
    }

    /**
     * @test
     */
    public function it_works_with_objects_of_type(): void
    {
        $date = new \DateTimeImmutable();
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => $date,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result = $access->objectOfType(\DateTimeImmutable::class, 'Foo', 'Bar', 'Baz');

        static::assertSame($date, $result);
    }

    /**
     * @test
     */
    public function it_works_with_datetime_immutable_parsing(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => '2020-01-02 14:30:55',
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result = $access->dateTimeImmutable('Y-m-d H:i:s', 'Foo', 'Bar', 'Baz');

        static::assertSame('2020-01-02 14:30:55', $result->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_raises_an_exception_on_datetime_format_missmatch(): void
    {
        $this->expectException(ArrayAccessFailed::class);
        $this->expectExceptionMessage('[Array path: Foo.Bar.Baz] Could not get datetime object at "Baz" with format "Y-m-d H:i:s" from value "2020-01-02T14:30:55"');

        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => '2020-01-02T14:30:55',
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $access->dateTimeImmutable('Y-m-d H:i:s', 'Foo', 'Bar', 'Baz');
    }

    /**
     * @test
     */
    public function it_works_with_array_pos(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    [
                        'Baz1' => 'Buz1',
                    ],
                    [
                        'Baz2' => 'Buz2',
                    ],
                    [
                        'Baz3' => 'Buz3',
                    ],
                ],
            ],
        ];

        $access = ArrayAccess::create($testArray);
        $result1 = $access->string('Foo', 'Bar', '0', 'Baz1');
        $result2 = $access->string('Foo', 'Bar', '1', 'Baz2');
        $result3 = $access->string('Foo', 'Bar', '2', 'Baz3');

        static::assertSame('Buz1', $result1);
        static::assertSame('Buz2', $result2);
        static::assertSame('Buz3', $result3);
    }

    /**
     * @test
     * @dataProvider invalidInputProvider
     */
    public function it_raises_an_exception_on_invalid_input_type($value, $message): void
    {
        $this->expectException(ArrayAccessFailed::class);
        $this->expectExceptionMessage($message);

        ArrayAccess::create($value);
    }

    public function invalidInputProvider(): \Generator
    {
        yield [new \stdClass(), 'Given parameter "object" is not an array'];
        yield [1, 'Given parameter "integer" is not an array'];
        yield ['some', 'Given parameter "string" is not an array'];
    }
}
