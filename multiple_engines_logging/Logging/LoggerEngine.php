<?php
namespace Hyperframework\Logging;

use Closure;
use Hyperframework\Common\Config;
use Hyperframework\Common\ConfigException;

class LoggerEngine {
    private $loggerName;
    private $level;
    private $writer;
    private $formatter;

    /**
     * @param string $loggerName
     */
    public function __construct($loggerName) {
        $this->loggerName = $loggerName;
    }

    /**
     * @param int $level
     * @param mixed $mixed
     * @return void
     */
    public function log($level, $mixed) {
        if ($level > $this->getLevel()) {
            return;
        }
        if ($mixed instanceof Closure) {
            $data = $mixed();
        } else {
            $data = $mixed;
        }
        if (is_array($data)) {
            $message = isset($data['message']) ? $data['message'] : null;
            $time = isset($data['time']) ? $data['time'] : null;
            $logRecord = new LogRecord($level, $message, $time);
        } else {
            $logRecord = new LogRecord($level, $data);
        }
        $this->handleLogRecord($logRecord);
    }

    /**
     * @param int $level
     * @return void
     */
    public function setLevel($level) {
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getLevel() {
        if ($this->level === null) {
            $configName = $this->getLoggerName() . '.log_level';
            $name = Config::getString($configName, '');
            if ($name !== '') {
                $level = LogLevel::getCode($name);
                if ($level === null) {
                    throw new ConfigException(
                        "Log level '$name' is invalid, set using config "
                            . "'$configName'. The available log levels are: "
                            . "DEBUG, INFO, NOTICE, WARNING, ERROR, FATAL, OFF."
                    );
                }
                $this->level = $level;
            } else {
                $this->level = LogLevel::INFO;
            }
        }
        return $this->level;
    }

    /**
     * @param LogRecord $logRecord
     * @return void
     */
    protected function handleLogRecord($logRecord) {
        $formatter = $this->getFormatter();
        $logRecord = $formatter->format($logRecord);
        $writer = $this->getWriter();
        $writer->write($logRecord);
    }

    /**
     * @return LogFormatter
     */
    protected function getFormatter() {
        if ($this->formatter === null) {
            $class = Config::getClass(
                $this->getLoggerName() . '.log_formatter_class',
                $this->getDefaultFormatterClass()
            );
            $this->formatter = new $class;
        }
        return $this->formatter;
    }

    /**
     * @return LogWriter
     */
    protected function getWriter() {
        if ($this->writer === null) {
            $class = Config::getClass(
                $this->getLoggerName() . '.log_writer_class',
                $this->getDefaultWriterClass()
            );
            $this->writer = new $class;
            $this->writer->setPath(Config::getString(
                $this->getLoggerName() . '.log_path',
                $this->getDefaultPath()
            ));
        }
        return $this->writer;
    }

    /**
     * @return string
     */
    protected function getLoggerName() {
        return $this->loggerName;
    }

    /**
     * @return string
     */
    protected function getDefaultFormatterClass() {
        return LogFormatter::class;
    }

    /**
     * @return string
     */
    protected function getDefaultWriterClass() {
        return LogWriter::class;
    }

    /**
     * @return string
     */
    protected function getDefaultPath() {
        return 'log' . DIRECTORY_SEPARATOR . 'app.log';
    }
}
