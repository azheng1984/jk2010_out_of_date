<?php
namespace Hyperframework\Common;

class FloatValidator extends NumberValidator {
    /**
     * @param mixed $target
     * @param array $metadata
     * @param array $options
     * @return ValidationError
     */
    public static function validate(
        $target, array $metadata = [], array $options = []
    ) {
        if ($target !== null) {
            if (filter_var($target, FILTER_VALIDATE_FLOAT) === false) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " should be a float.";
                return static::buildValidationError(
                    $metadata, 'type', $value, $errorMessage
                );
            }
        }
        return parent::validate($target, $metadata, $options);
    }
}
