<?php
namespace Hyperframework\Common;

class IntegerValidator extends NumberValidator {
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
            if (filter_var($target, FILTER_VALIDATE_INT) === false) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . "' should be an integer.'";
                return static::buildValidationError(
                    $metadata, 'type', $target, $errorMessage
                );
            }
        }
        return parent::validate($target, $metadata, $options);
    }
}
