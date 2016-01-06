<?php
namespace Hyperframework\Common;

class NumberValidator extends Validator {
    /**
     * @param mixed $target
     * @param array $metadata
     * @param array $options
     * @return ValidationError
     */
    public static function validate(
        $target, array $metadata = [], array $options = []
    ) {
        $result = parent::validate($target, $metadata, $options);
        if ($result !== null || $target === null) {
            return $result;
        }
        $min = static::getRuleValue($metadata, 'min');
        if ($min !== null) {
            if (bccomp($target, $min) === -1) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " should be greater or equal to " . $min . '.';
                return static::buildValidationError(
                    $metadata, 'min', $target, $errorMessage
                );
            }
        }
        $max = static::getRuleValue($metadata, 'max');
        if ($max !== null) {
            if (bccomp($target, $max) === 1) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " should be less or equal to " . $max. '.';
                return static::buildValidationError(
                    $metadata, 'max', $target, $errorMessage
                );
            }
        }
        $lessThan = static::getRuleValue($metadata, 'less_than');
        if ($lessThan !== null) {
            if (bccomp($target, $lessThan) !== -1) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " should be less " . $lessThan . '.';
                return static::buildValidationError(
                    $metadata, 'less_than', $target, $errorMessage
                );
            }
        }
        $greaterThan = static::getRuleValue($metadata, 'greater_than');
        if ($greaterThan !== null) {
            if (bccomp($target, $greaterThan) !== -1) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " should be greater " . $greaterThan . '.';
                return static::buildValidationError(
                    $metadata, 'greater_than', $target, $errorMessage
                );
            }
        }
    }
}
