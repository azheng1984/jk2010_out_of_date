<?php
namespace Hyperframework\Common;

class TimeValidator extends Validator {
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
            $regex = '/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
            if (preg_match($regex, $target) === 0) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " is not a valid time.";
                return static::buildValidationError(
                    $metadata, 'type', $target, $errorMessage
                );
            }
        }
        return parent::validate($target, $metadata, $options);
    }
}
