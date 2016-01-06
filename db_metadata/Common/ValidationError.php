<?php
namespace Hyperframework\Common;

class ValidationError {
    private $target;
    private $message;
    private $code;

    /**
     * @param mixed $target
     * @param string $message
     * @param int|string $code
     */
    public function __construct($target, $message = '', $code = 0) {
        $this->target = $target;
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return int|string
     */
    public function getCode() {
        return $this->code;
    }
}
