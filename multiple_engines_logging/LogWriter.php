<?php
namespace Hyperframework\Logging;

use RuntimeException;
use Hyperframework\Common\Config;
use Hyperframework\Common\FileLock;

class LogWriter {
    private $path;

    /**
     * @param string $text
     * @return void
     */
    public function write($text) {
        FileLock::run(
            $this->getPath(),
            'a',
            LOCK_EX,
            function($handle) use ($text) {
                $status = fwrite($handle, $text);
                if ($status !== false) {
                    $status = fflush($handle);
                }
                if ($status !== true) {
                    throw new RuntimeException(
                        "Failed to write file '{$this->getPath()}'."
                    );
                }
            }
        );
    }

    /**
     * @param string $text
     * @return void
     */
    public function writeLine($text) {
        $this->write($text . PHP_EOL);
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath() {
        return $this->path;
    }
}
