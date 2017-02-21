<?php
namespace Hyperframework\Logging;

use Exception;
use Hyperframework\Common\Config;
use Hyperframework\Logging\Test\TestCase as Base;

class LogWriterTest extends Base {
    protected function tearDown() {
        $this->deleteAppLogFile();
        $path = Config::getAppRootPath() . '/log/tmp/app.log';
        if (file_exists($path)) {
            unlink($path);
            rmdir(dirname($path));
        }
        parent::tearDown();
    }

    public function testAppendLogFile() {
        $writer = new LogWriter;
        $writer->setPath('log/tmp/app.log');
        $writer->write('record-1' . PHP_EOL);
        $writer->write('record-2' . PHP_EOL);
        return $this->assertSame(
            'record-1' . PHP_EOL . 'record-2' . PHP_EOL,
            file_get_contents(
                Config::getAppRootPath() . '/log/tmp/app.log'
            )
        );
    }

    public function testCreateLogFolder() {
        $writer = new LogWriter;
        $writer->setPath('log/tmp/app.log');
        $writer->write('content');
        $this->assertSame('content', file_get_contents(
            Config::getAppRootPath() . '/log/tmp/app.log'
        ));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFailedToMakeLogFolder() {
        Config::set('hyperframework.logging.log_path', 'log/tmp/app.log');
        $this->writeToReadOnlyFolder();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFailedToOpenLogFile() {
        $this->writeToReadOnlyFolder();
    }

    private function writeToReadOnlyFolder() {
        set_error_handler(function() {});
        chmod(Config::getAppRootPath() . '/log', 0555);
        $writer = new LogWriter;
        $writer->setPath('log/app.log');
        try {
            $writer->write('content');
        } catch (Exception $e) {
            restore_error_handler();
            chmod(Config::getAppRootPath() . '/log', 0755);
            throw $e;
        }
    }
}
