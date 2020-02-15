# Array Access

Library to ease array access handling

## Install
`composer require mschindler83/array-access`

## Features

 - Savely access typed values from a given array
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

