<?php
namespace Hyperframework\Common;

class DateTimeValidator extends Validator {
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
            $tmp = explode(' ', $target, 2);
            if (count($tmp) !== 2
                || DateValidator::validate($tmp[0]) !== null
                || TimeValidator::validate($tmp[1]) !== null
            ) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " is not a valid datetime.";
                return static::buildValidationError(
                    $metadata, 'type', $target, $errorMessage
                );
            }
        }
        return parent::validate($target, $metadata, $options);
    }
}
