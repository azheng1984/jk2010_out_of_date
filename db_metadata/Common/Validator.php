<?php
namespace Hyperframework\Common;

class Validator {
    /**
     * @param mixed $target
     * @param array $metadata
     * @param array $options
     * @return ValidationError
     */
    public static function validate(
        $target, array $metadata = [], array $options = []
    ) {
        if ($target === null) {
            $isNotNull = static::getRuleValue($metadata, 'not_null');
            if ((bool)$isNotNull === true) {
                $errorMessage = ucfirst(static::getTargetName($options))
                    . " should not be null.";
                return static::buildValidationError(
                    $metadata, 'not_null', null, $errorMessage
                );
            }
        }
        $in = static::getRuleValue($metadata, 'in');
        if ($in !== null) {
            if (is_array($in) === false) {
                $type = gettype($in);
                throw new ValidationException(
                    ucfirst(static::getMetadataName($options)) . " is invalid, "
                        . "the value of rule 'in' for "
                        . static::getTargetName($options)
                        . " should be an array, $type given."
                );
            }
            if (in_array($target, $in) === false) {
                $errorMessage = "The value of "
                    . static::getTargetName($options) . " is invalid.";
                return static::buildValidationError(
                    $metadata, 'in', $target, $errorMessage
                );
            }
        }
    }

    /**
     * @param array $metadata
     * @param string $ruleName
     * @return mixed
     */
    public static function getRuleValue(array $metadata, $ruleName) {
        if (isset($metadata[$ruleName]) === false) {
            return;
        }
        $result = $metadata[$ruleName];
        if (is_array($result) === false) {
            return $result;
        }
        if (isset($result['value'])) {
            return $result['value'];
        } else {
            return $result;
        }
    }

    /**
     * @param array $options
     * @return string
     */
    protected static function getTargetName(array $options) {
        return isset($options['target_name']) ?
            $options['target_name'] : 'the target';
    }

    /**
     * @param array $options
     * @return string
     */
    protected static function getMetadataName(array $options) {
        return isset($options['metadata_name']) ?
            $options['metadata_name'] : 'the metadata';
    }

    /**
     * @param array $metadata
     * @param string $ruleName
     * @param mixed $target
     * @param string $message
     * @param int|string $code
     * @return ValidationError
     */
    protected static function buildValidationError(
        array $metadata, $ruleName, $target, $message = '', $code = 0
    ) {
        if (isset($metadata[$ruleName]['error_code'])
            || isset($metadata[$ruleName]['error_message'])
        ) {
            if (isset($metadata[$ruleName]['error_code'])) {
                $code = $metadata[$ruleName]['error_code'];
            }
            if (isset($metadata[$ruleName]['error_message'])) {
                $message = $metadata[$ruleName]['error_message'];
            }
        } else {
            if (isset($metadata['error_code'])) {
                $code = $metadata['error_code'];
            } elseif (isset($metadata['error_message'])) {
                $message = $metadata['error_message'];
            }
        }
        return new ValidationError($target, $message, $code);
    }
}
