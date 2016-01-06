<?php
namespace Hyperframework\Common;

class DecimalValidator extends NumberValidator {
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
            $regex = '/^-?((\d+\.?\d*)|(\.\d+))$/';
            if (preg_match($regex, $target) === 0) {
                $errorMessage =  ucfirst(static::getTargetName($options))
                    . "' should be a decimal.'";
                return static::buildValidationError(
                    $metadata, 'type', $target, $errorMessage
                );
            }
            $scale = static::getRuleValue($metadata, 'scale');
            if ($scale !== null) {
                $scale = (int)$scale;
                if ($scale < 0) {
                    throw new ValidationException(
                        ucfirst(static::getMetadataName($options)) . " is invalid, "
                            . "the scale of " . static::getTargetName($options)
                            . " should not be less then 0."
                    );
                }
                $tmp = explode('.', $target, 2);
                $length = isset($tmp[1]) ? strlen($tmp[1]) : 0;
                if ($length !== $scale) {
                    $errorMessage = ucfirst(static::getTargetName($options))
                        . " is invalid, the number of digits"
                        . " to the right of the decimal point"
                        . " should be "  . $scale . ".'";
                    return static::buildValidationError(
                        $metadata, 'scale', $target, $errorMessage
                    );
                }
            }
        }
        return parent::validate($target, $metadata, $options);
    }
}
