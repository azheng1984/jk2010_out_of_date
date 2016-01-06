<?php
namespace Hyperframework\Common;

class UrlValidator extends StringValidator {
    /**
     * @param mixed $target
     * @param array $metadata
     * @param array $options
     * @return ValidationError
     */
    public static function validate(
        $target, array $metadata = [], array $options = []
    ) {
        if ($target !== null
            && filter_var($target, FILTER_VALIDATE_URL) === false
        ) {
            $errorMessage = ucfirst(static::getTargetName($options))
                . " is not a valid url.";
            return static::buildValidationError(
                $metadata, 'type', $value, $errorMessage
            );
        }
        return parent::validate($target, $metadata, $options);
    }
}
