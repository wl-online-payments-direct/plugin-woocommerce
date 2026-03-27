# Dhii - Validator
A thin, standards-compliant validator implementation of [`dhii/validation-interface`][].

## Details
This is a super-lightweight validation library that allows easy creation
of validation structures of arbitrary depth.

### Examples
A validator ensures values are valid usernames.
It is a composite validator, comprised of 2 simple validators:

1. Ensures values are alphanumeric strings.
2. Ensures value lengths are less than or equal to 30 characters.

This validator hierarchy throws a hierarchy of exceptions when failing:

1. Composite validator's failure exception reports how many failures occurred, and exposes failure exceptions from
the simple validators.
2. Those in turn report what exactly was invalid.

```php
use Dhii\Validation\Exception\ValidationFailedExceptionInterface;use Dhii\Validator\CallbackValidator;
use Dhii\Validator\CompositeValidator;

$alphanum = new CallbackValidator(function ($value) {
    if (preg_match('![^\d\w]!', $value, $matches)) {
        return sprintf('Value "%1$s" must be alphanumeric, but contains char "%2$s"', $value, $matches[0][0]);
    }
});
$chars30 = new CallbackValidator(function ($value) {
    $length = strlen($value);
    if (!($length <= 30)) {
        return sprintf('Value "%1$s" is limited to 30 characters, but is %2$d characters long', $value, $length);
    }
});
$usernameValidator = new CompositeValidator([$alphanum, $chars30]);

// Valid username: nothing happens
$usernameValidator->validate('abcdef');

// Invalid percentage value: exception thrown
try {
    $percentValidator->validate('abcdefghijklmnopqrstuvwxyz!@#$%');
} catch (ValidationFailedExceptionInterface $e) {
    // Validation failed with 2 errors.
    echo $e->getMessage();
    // 1: Value "abcdefghijklmnopqrstuvwxyz!@#$%" must be alphanumeric, but contains char "!"
    // 2: Value "abcdefghijklmnopqrstuvwxyz!@#$%" is limited to 30 characters, but is 31 characters long
    foreach ($e->getValidationErrors() as $error) {
        echo (string) $error;
    }
}
```

[`dhii/validation-interface`]: https://github.com/Dhii/validation-interface
