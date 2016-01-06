<?php
namespace Hyperframework\Common;

class StringValidator extends Validator {
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
        $actualLength = strlen($value);
        $length = static::getRuleValue($metadata, 'length');
        if ($length !== null) {
            $length = (int)$length;
            if ($actualLength !== $length) {
                $errorMessage = "The length of "
                    . static::getTargetName($options)
                    . " should be equal to " . $length . '.';
                return static::buildValidationError(
                    $metadata, 'length', $target, $errorMessage
                );
                continue;
            }
        } else {
            $maxLength = static::getRuleValue($metadata, 'max_length');
            if ($maxLength !== null) {
                if ($actualLength > $maxLength) {
                    $errorMessage = "The length of "
                        . static::getTargetValidationName($options)
                        . " should be less or equal to "
                        . $maxLength . ".";
                    return static::buildError(
                        $metadata, 'max_length', $target, $errorMessage
                    );
                }
            }
            $minLength = static::getRuleValue($metadata, 'min_length');
            if ($maxLength !== null) {
                if ($actualLength < $minLength) {
                    $errorMessage = "The length of "
                        . static::getTargetName($options)
                        . " should be greater or equal to "
                        . $minLength . '.';
                    return static::buildValidationError(
                        $metadata, 'min_length', $target, $errorMessage
                    );
                }
            }
        }
    }
}
