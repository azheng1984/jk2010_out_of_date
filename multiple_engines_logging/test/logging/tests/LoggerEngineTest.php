<?php
namespace Hyperframework\Logging;

use stdClass;
use Datetime;
use Hyperframework\Common\Config;
use Hyperframework\Logging\Test\TestCase as Base;

class LoggerEngineTest extends Base {
    private $loggerEngine;

    protected function setUp() {
        parent::setUp();
        $this->loggerEngine =
            $this->getMockBuilder('Hyperframework\Logging\LoggerEngine')->setConstructorArgs(['hyperframework.logging.logger'])
                ->setMethods(['handleLogRecord'])->getMock();
    }

    public function testGenerateLogUsingClosure() {
        $this->mockHandleLogRecord(function($logRecord) {
            $this->assertSame(LogLevel::ERROR, $logRecord->getLevel());
            $this->assertSame('message', $logRecord->getMessage());
        });
        $this->loggerEngine->log(LogLevel::ERROR, function() {
            return 'message';
        });
    }

    public function testLogString() {
        $this->mockHandleLogRecord(function($logRecord) {
            $this->assertSame(LogLevel::ERROR, $logRecord->getLevel());
            $this->assertSame('message', $logRecord->getMessage());
        });
        $this->loggerEngine->log(LogLevel::ERROR, 'message');
    }

    public function testLogEmptyArray() {
        $this->mockHandleLogRecord(function($logRecord) {
            $this->assertInstanceOf(
                'Hyperframework\Logging\LogRecord', $logRecord
            );
        });
        $this->loggerEngine->log(LogLevel::ERROR, []);
    }

    public function testLogCustomTime() {
        $time = new DateTime;
        $this->mockHandleLogRecord(function($logRecord) use ($time) {
            $this->assertSame($time, $logRecord->getTime());
        });
        $this->loggerEngine->log(LogLevel::ERROR, ['time' => $time]);
    }

    public function testDefaultLevel() {
        $this->mockHandleLogRecord(function($logRecord) {
            $this->assertSame(LogLevel::INFO, $logRecord->getLevel());
        });
        $this->loggerEngine->log(LogLevel::DEBUG, 'message');
        $this->loggerEngine->log(LogLevel::INFO, 'message');
    }

    public function testChangeLevel() {
        $this->mockHandleLogRecord(function($logRecord) {
            $this->assertSame(LogLevel::ERROR, $logRecord->getLevel());
        });
        $this->loggerEngine->setLevel(LogLevel::ERROR);
        $this->loggerEngine->log(LogLevel::WARNING, 'message');
        $this->loggerEngine->log(LogLevel::ERROR, 'message');
    }

    public function testChangeLevelUsingConfig() {
        $this->mockHandleLogRecord(function($logRecord) {
            $this->assertSame(LogLevel::ERROR, $logRecord->getLevel());
        });
        Config::set('hyperframework.logging.logger.log_level', 'ERROR');
        $this->loggerEngine->log(LogLevel::WARNING, 'message');
        $this->loggerEngine->log(LogLevel::ERROR, 'message');
    }

    /**
     * @expectedException Hyperframework\Logging\LoggingException
     */
    public function testInvalidTime() {
        $this->loggerEngine->log(LogLevel::ERROR, ['time' => 'invalid']);
    }

    /**
     * @expectedException Hyperframework\Common\ConfigException
     */
    public function testInvalidLevelConfig() {
        Config::set('hyperframework.logging.logger.log_level', 'UNKNOWN');
        $this->loggerEngine->log(LogLevel::ERROR, 'message');
    }

    private function mockHandleLogRecord($handleCallback) {
        $this->loggerEngine->method('handleLogRecord')->will(
            $this->returnCallback($handleCallback)
        );
    }

    public function testHandleLog() {
        $logRecord = new LogRecord(LogLevel::ERROR, null);
        $formatter = $this->getMock('Hyperframework\Logging\LogFormatter');
        $formatter->expects($this->once())->method('format')
            ->with($this->identicalTo($logRecord))->willReturn('text');
        $writer = $this->getMock('Hyperframework\Logging\LogWriter');
        $writer->expects($this->once())->method('write')
            ->with($this->equalTo('text'));
        $engine = $this->getMockBuilder(
            'Hyperframework\Logging\LoggerEngine'
        )->setMethods(['getFormatter', 'getWriter'])->setConstructorArgs(['hyperframework.logging.logger'])->getMock();
        $engine->method('getFormatter')->willReturn($formatter);
        $engine->method('getWriter')->willReturn($writer);
        $this->callProtectedMethod($engine, 'handleLogRecord', [$logRecord]);
    }

    public function testDefaultLogWriter() {
        $engine = new LoggerEngine('hyperframework.logging.logger');
        $this->assertTrue(
            $this->callProtectedMethod($engine, 'getWriter')
                instanceof LogWriter
        );
    }

    /**
     * @expectedException Hyperframework\Common\ClassNotFoundException
     */
    public function testInvalidLogWriter() {
        Config::set('hyperframework.logging.logger.log_writer_class', 'Unknown');
        $engine = new LoggerEngine('hyperframework.logging.logger');
        //$handler = new LogHandler;
        $this->callProtectedMethod($engine, 'getWriter');
    }

    public function testCustomLogWriter() {
        Config::set(
            'hyperframework.logging.logger.log_writer_class',
            'Hyperframework\Logging\Test\CustomLogWriter'
        );
        $engine = new LoggerEngine('hyperframework.logging.logger');
        //$handler = new LogHandler;
        $this->assertTrue(
            $this->callProtectedMethod($engine, 'getWriter')
                instanceof Test\CustomLogWriter
        );
    }

    public function testDefaultLogFormatter() {
        $engine = new LoggerEngine('hyperframework.logging.logger');
        //$handler = new LogHandler;
        $this->assertTrue(
            $this->callProtectedMethod($engine, 'getFormatter')
                instanceof LogFormatter
        );
    }

    /**
     * @expectedException Hyperframework\Common\ClassNotFoundException
     */
    public function testInvalidLogFormatter() {
        Config::set('hyperframework.logging.logger.log_formatter_class', 'Unknown');
        $engine = new LoggerEngine('hyperframework.logging.logger');
        $this->callProtectedMethod($engine, 'getFormatter');
    }

    public function testCustomLogFormatter() {
        Config::set(
            'hyperframework.logging.logger.log_formatter_class',
            'Hyperframework\Logging\Test\CustomLogFormatter'
        );
        $engine = new LoggerEngine('hyperframework.logging.logger');
        $this->assertTrue(
            $this->callProtectedMethod($engine, 'getFormatter')
                instanceof Test\CustomLogFormatter
        );
    }
}
