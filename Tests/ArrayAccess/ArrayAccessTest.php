<?php
declare(strict_types=1);

namespace Tests\mschindler83\ArrayAccess;

use BadMethodCallException;
use Mschindler83\ArrayAccess\ArrayAccess;
use Mschindler83\ArrayAccess\ArrayAccessFailed;
use Mschindler83\ArrayAccess\ArrayAccessValidationFailed;
use Mschindler83\ArrayAccess\DotAnnotation\SimpleDotAnnotation;
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
    public function it_validates_with_json_schema_validator(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => true,
        ];

        $access = ArrayAccess::createWithJsonSchemaValidation($data, \file_get_contents(__DIR__ . '/../Fixture/json-schema.json'));
        static::assertSame('value1', $access->string('key1'));
        static::assertTrue($access->bool('key2'));
    }

    /**
     * @test
     */
    public function it_raises_an_exception_on_failed_json_schema_validation(): void
    {
        $this->expectException(ArrayAccessValidationFailed::class);
        $this->expectExceptionMessage('Json schema validation failed: Error: [minLength], Data pointer: [key1], Error: [type], Data pointer: [key2], Error: [additionalProperties], Data pointer: []');

        $data = [
            'key1' => 'v',
            'key2' => '1',
            'key3' => 'some-other-value',
        ];

        ArrayAccess::createWithJsonSchemaValidation($data, \file_get_contents(__DIR__ . '/../Fixture/json-schema.json'));
    }

    /**
     * @test
     */
    public function it_can_be_created_from_dot_annotation(): void
    {
        $access = ArrayAccess::newFromDotAnnotation(SimpleDotAnnotation::create('key1.key2.2.key3', 'the-value'));

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
    public function it_can_be_created_from_dot_annotation_with_empty_keys(): void
    {
        $access = ArrayAccess::newFromDotAnnotation(SimpleDotAnnotation::create('key1...key2', 'the-value'));

        static::assertSame(
            [
                'key1' => [
                    0 => [
                        0 => [
                            'key2' => 'the-value'
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
    public function it_can_be_created_from_multiple_dot_annotation(): void
    {
        $access = ArrayAccess::newFromDotAnnotation(
            SimpleDotAnnotation::create('key1.some', 'some-value'),
            SimpleDotAnnotation::create('key1.key2.0.key3', 'the-value-1'),
            SimpleDotAnnotation::create('key1.key2.1.key3', 'the-value-2'),
            SimpleDotAnnotation::create('key1.key2.2.key3', 'the-value-3'),
            SimpleDotAnnotation::create('key1.key2.3.key3', 'the-value-4'),
            SimpleDotAnnotation::create('key1.key2.3.key4', 'the-value-5')
        );

        static::assertSame(
            [
                'key1' => [
                    'some' => 'some-value',
                    'key2' => [
                        0 => [
                            'key3' => 'the-value-1',
                        ],
                        1 => [
                            'key3' => 'the-value-2',
                        ],
                        2 => [
                            'key3' => 'the-value-3',
                        ],
                        3 => [
                            'key3' => 'the-value-4',
                            'key4' => 'the-value-5',
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
        $access = $access->writeAtPath('new-value', 'Foo', 'Bar', 'New');
        $access = $access->writeAtPath('new-value-2', 'Foo', 'Bar', 'New-2');

        static::assertSame(
            [
                'Foo' => [
                    'Bar' => [
                        'Baz' => 'Buz',
                        'New' => 'new-value',
                        'New-2' => 'new-value-2',
                    ],
                ],
            ],
            $access->data()
        );
    }

    /**
     * @test
     */
    public function it_can_write_value_at_single_path(): void
    {
        $access = ArrayAccess::create([]);
        $newAccess = $access->writeAtPath('new-value', 'Foo');

        static::assertSame('new-value', $newAccess->string('Foo'));
    }

    /**
     * @test
     */
    public function it_can_write_empty_array(): void
    {
        $access = ArrayAccess::create([]);
        $access = $access->writeAtPath([], '0');

        static::assertSame([], $access->array(0));
    }

    /**
     * @test
     */
    public function it_can_write_at_path_on_empty_access(): void
    {
        $access = ArrayAccess::create([]);
        $newAccess = $access->writeAtPath('new-value', 'Foo', 'Bar', 'New');

        static::assertSame(
            [
                'Foo' => [
                    'Bar' => [
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
    public function it_writes_on_zero_index_if_empty_path_provided(): void
    {
        $access = ArrayAccess::create([]);
        $access = $access->writeAtPath('foo');

        static::assertSame(['foo'], $access->data());
    }

    /**
     * @test
     */
    public function it_writes_null_value_on_zero_index(): void
    {
        $access = ArrayAccess::create([]);
        $access = $access->writeAtPath(null);

        static::assertSame([0 => null], $access->data());
    }

    /**
     * @test
     */
    public function it_can_write_at_path_with_complex_data(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => 'Buz',
                    'next-key' => 'next-value',
                    'next-array' => [
                        'some' => 'some-value',
                        'other' => 'other-value'
                    ],
                ],
            ],
        ];

        $access = ArrayAccess::create($testArray);
        $newAccess = $access->writeAtPath(
            [
                'some' => 'some-value-2',
                'other' => 'new-other',
                'new' => 'new',
            ],
            'Foo', 'Bar', 'next-array'
        );

        static::assertSame(
            [
                'Foo' => [
                    'Bar' => [
                        'Baz' => 'Buz',
                        'next-key' => 'next-value',
                        'next-array' => [
                            'some' => 'some-value-2',
                            'other' => 'new-other',
                            'new' => 'new',
                        ],
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
    public function it_raises_an_exception_on_not_existing_path(): void
    {
        $this->expectException(ArrayAccessFailed::class);
        $this->expectExceptionMessage('Path not found');

        $access = ArrayAccess::create(['foo' => ['bar' => null]]);
        $access->int('foo', 'bar', 'baz');
    }

    /**
     * @test
     */
    public function it_raises_an_exception_on_unknown_method_call(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method "unknownMethod" not found');

        ArrayAccess::create([])->unknownMethod();
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
    public function it_works_with_nullable_strings(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => null,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result = $access->stringOrNull('Foo', 'Bar', 'Baz');

        static::assertNull($result);
    }

    /**
     * @test
     */
    public function it_raises_an_exception_on_value_other_than_null_when_requesting_nullable(): void
    {
        $this->expectException(ArrayAccessFailed::class);
        $this->expectExceptionMessage('[Array path: Foo.Bar.Baz] Could not get value for "Baz". Invalid type "integer". Expected type: "stringOrNull"');

        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => 1,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $access->stringOrNull('Foo', 'Bar', 'Baz');
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
        $result = $access->int('Foo', 'Bar', 'Baz');

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
    public function it_works_with_custom_callback(): void
    {
        $testArray1 = [
            'Foo' => [
                'Bar' => [
                    'Baz' => 'Buz',
                ],
            ],
        ];
        $testArray2 = [
            'Foo' => [
                'Bar' => [
                    'Baz' => 99,
                ],
            ],
        ];
        $customCallback = function($value) {
            return is_string($value) || is_int($value);
        };

        $access1 = ArrayAccess::create($testArray1);
        $result1 = $access1->callback($customCallback, 'Foo', 'Bar', 'Baz');

        $access2 = ArrayAccess::create($testArray2);
        $result2 = $access2->callback($customCallback, 'Foo', 'Bar', 'Baz');

        static::assertSame('Buz', $result1);
        static::assertSame(99, $result2);
    }

    /**
     * @test
     */
    public function it_raises_an_exception_on_callback_restriction(): void
    {
        $this->expectException(ArrayAccessFailed::class);
        $this->expectExceptionMessage('[Array path: Foo.Bar.Baz] Could not get value for "Baz". Reason: Callback restriction');

        $testArray = [
            'Foo' => [
                    'Bar' => [
                    'Baz' => 'Buz',
                ],
            ],
        ];
        $customCallback = function($value) {
            return is_string($value) && in_array($value, ['Allowed value 1', 'Allowed value 2']);
        };
        $access = ArrayAccess::create($testArray);
        $access->callback($customCallback, 'Foo', 'Bar', 'Baz');
    }

    /**
     * @test
     */
    public function it_raises_an_exception_on_object_type_missmatch(): void
    {
        $this->expectException(ArrayAccessFailed::class);
        $this->expectExceptionMessage('[Array path: Foo.Bar.Baz] Could not get value for "Baz". Invalid type "DateTimeImmutable". Expected type: "DateTime"');

        $date = new \DateTimeImmutable();
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => $date,
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $access->objectOfType(\DateTime::class, 'Foo', 'Bar', 'Baz');
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

        static::assertInstanceOf(\DateTimeImmutable::class, $result);
        static::assertSame('2020-01-02 14:30:55', $result->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_raises_an_exception_on_datetime_immutable_format_missmatch(): void
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
    public function it_works_with_datetime_parsing(): void
    {
        $testArray = [
            'Foo' => [
                'Bar' => [
                    'Baz' => '2020-01-02 14:30:55',
                ],
            ],
        ];
        $access = ArrayAccess::create($testArray);
        $result = $access->dateTime('Y-m-d H:i:s', 'Foo', 'Bar', 'Baz');

        static::assertInstanceOf(\DateTime::class, $result);
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
        $access->dateTime('Y-m-d H:i:s', 'Foo', 'Bar', 'Baz');
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

    /**
     * @test
     */
    public function it_raises_an_exception_on_invalid_method_call(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method "double" not found');

        $access = ArrayAccess::create([]);
        $access->double();
    }
}
