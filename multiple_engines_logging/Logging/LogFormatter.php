<?php
namespace Hyperframework\Logging;

class LogFormatter {
    /**
     * @param LogRecord $logRecord
     * @return string
     */
    public function format($logRecord) {
        $time = $logRecord->getTime();
        $result = $time->format('Y-m-d H:i:s')
            . ' [' . LogLevel::getName($logRecord->getLevel()) . '] '
            . getmypid();
        $message = (string)$logRecord->getMessage();
        if ($message !== '') {
            $result .= ' | ' . str_replace("\n", "\n  ", $message);
        }
        return $result . PHP_EOL;
    }
}
