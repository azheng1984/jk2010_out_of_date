<?php
namespace Hyperframework\Common;

class DateValidator extends Validator {
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
            $regex = '/^(\d{4})-(\d{2})-(\d{2})/';
            if (preg_match($regex, $value, $matches) === 1) {
                if (checkdate($matches[2], $matches[3], $matches[1]) === false) {
                    $errorMessage = ucfirst(static::getTargetName($options))
                        . " is not a valid time.";
                    return static::buildValidationError(
                        $metadata, 'type', $target, $errorMessage
                    );
                }
            }
        }
        return parent::validate($target, $metadata, $options);
    }
}
