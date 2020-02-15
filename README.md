# Array Access
[![Build Status](https://img.shields.io/travis/mschindler83/array-access/master.svg)](https://travis-ci.org/mschindler83/array-access)
[![Latest Stable Version](https://img.shields.io/packagist/v/mschindler83/array-access.svg)](https://packagist.org/packages/mschindler83/array-access)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/mschindler83/array-access.svg)](https://scrutinizer-ci.com/g/mschindler83/array-access/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/mschindler83/array-access/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mschindler83/array-access/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/mschindler83/array-access/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)
[![Monthly Downloads](https://img.shields.io/packagist/dm/mschindler83/array-access.svg)](https://packagist.org/packages/mschindler83/array-access)


Library to ease array access handling

## Install
`composer require mschindler83/array-access`

## Features

 - Savely access typed values from a given array
 - Optional JSON schema validation
 - Support for datetime parsing
 - Define your own validation callback when retrieving values
 - Create a new array in form of "dot annotations"
 - Easily write values to a specific path of the array

## Usage Examples

### Creating access objects
#### Create access object from an array and access values

```
$array = [
    'key1' => [
        'key2' => [
            'key3' => 'the-value'
        ],
    ],
];

$access = ArrayAccess::create($array);

try {
    // Get the string value at the given path
    $value = $access->string('key1', 'key2', 'key3');
    
    // This will fail with an exception because we try to get an integer at the given path
    $invalidValue = $access->int('key1', 'key2', 'key3');
} catch (ArrayAccessFailed $e) {
    // handle errors
    echo $e->getMessage();
}
```

#### Create an array from "dot annotation"
```
$access = ArrayAccess::newFromDotAnnotation(
    SimpleDotAnnotation::create('key1.key2.2.key3', 'the-value-1'),
    SimpleDotAnnotation::create('key1.key2.2.key4', 'the-value-2')
);

$plainArray = $access->data();
```

Plain array will contain:

```
Array
(
  [key1] => Array
    (
      [key2] => Array
        (
          [2] => Array
            (
              [key3] => the-value-1
              [key4] => the-value-2
            )
        )
    )
)
```
### Array access with JSON schema validation
```
$data = [
    'key1' => 'value1',
    'key2' => true,
];

$access = ArrayAccess::createWithJsonSchemaValidation($data, \file_get_contents('json-schema.json'));

```

JSON schema: <json-schema.json>
```
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "key1": {
      "type": "string",
      "minLength": 3,
      "maxLength": 64,
      "pattern": "^[a-zA-Z0-9\\-]+(\\s[a-zA-Z0-9\\-]+)*$"
    },
    "key2": {
      "type": "boolean"
    }
  },
  "required": ["key1", "key2"],
  "additionalProperties": false
}
```

### Access values
```
$array = [
    'root' => [
        'string-value' => 'the-value',
        'int-value' => 10,
        'float-value' => 9.99,
        'bool-value' => true,
        'array-value' => [1, 2, 3],
        'datetime-value' => '2020-01-01 12:00:00',
        'object-value' => new \stdClass(),
        'custom' => 'Choice 1',
    ],
];

// Create the access object
$access = ArrayAccess::create($array);

// This will return the string "the-value"
$access->string('root', 'string-value');

// This will return the integer "10"
$access->int('root', 'int-value');

// This will return the float "9.99"
$access->float('root', 'float-value');

// This will return the bool "true"
$access->bool('root', 'bool-value');

// This will return the array "[1, 2, 3]"
$access->array('root', 'array-value');

// This will return a new ArrayAccess object
$access->arrayAccess('root', 'array-value');

// This will return a \DateTimeImmutable object
$access->dateTimeImmutable('Y-m-d H:i:s', 'root', 'datetime-value');

// This will return a \DateTime object
$access->dateTime('Y-m-d H:i:s', 'root', 'datetime-value');

// This will return the \stdClass object
$access->objectOfType(\stdClass::class, 'root', 'object-value');

// This will return a mixed, depending on the array content, but only if the custom validation passes
// In this case it will return the string "Choice 1"
$access->callback(
    function ($value) {
        return in_array($value, ['Choice 1', 'Choice 2', 'Choice 3']);
    },
    'root', 'custom'
);
```

### Write to a path

```
$access = ArrayAccess::create([]);
$access->writeAtPath('the-value', 'at', 'some', 'path');
$theValue = $access->string('at', 'some', 'path');
```

