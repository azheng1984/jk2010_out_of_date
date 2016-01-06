<?php
namespace Hyperframework\Common;

class EmailValidator extends StringValidator {
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
            if (filter_var($target, FILTER_VALIDATE_EMAIL) === false) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " is not a valid email address.";
                return static::buildError(
                    $metadata, 'type', $value, $errorMessage
                );
            }
        }
        return parent::validate($target, $metadata, $options);
    }
}
