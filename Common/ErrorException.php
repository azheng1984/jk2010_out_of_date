<?php
namespace Hyperframework\Common;

use ErrorException as Base;

class ErrorException extends Base {
    private $isFatal;
    private $context;
    private $sourceTrace;

    public function __construct(
        $message = '',
        $code = 0,
        $severity = 1,
        $file = __FILE__,
        $line = __LINE__,
        array $context = null,
        $sourceTrace = null,
        $previous = null
    ) {
        parent::__construct(
            $message, $code, $severity, $file, $line, $previous
        );
        $this->context = $context;
    }

    public function getContext() {
        return $this->context;
    }

    public function isFatal() {
        if ($this->isFatal === null) {
            $this->isFatal = ErrorCodeHelper::isFatal($this->getSeverity());
        }
        return $this->isFatal;
    }

    public function getSourceTrace() {
        return $this->sourceTrace;
    }

    public function getSourceTraceAsString() {
        if ($this->sourceTrace === null) {
            return 'undefined';
        } else {
            $result = null;
            $index = 0;
            foreach ($this->sourceTrace as $item) {
                $result .= PHP_EOL . '#' . $index . ' '
                    . $item['file'] . '(' . $item['line'] . '): '
                    . $item['function'];
                ++$index;
            }
        }
    }

    public function __toString() {
        $message = 'exception \'' . get_called_class(). '\' with message \''
            . $this->getMessage() . '\' in ' . $this->getFile() . ':'
            . $this->getLine()
            . PHP_EOL . 'Stack trace:'
            . $this->getSourceTraceAsString();
        return $message;
    }
}
