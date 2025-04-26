<?php

namespace App\Logging;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
// use Monolog\Processor\IntrospectionProcessor;
// use Monolog\Processor\MemoryUsageProcessor;
// use Monolog\Processor\ProcessIdProcessor;

class SMSLogger
{
    private $logger;
    private $logPath;
    private $maxFiles;

    public function __construct($channel = 'app', $logName = 'sms.log', $maxFiles = 90)
    {
        $this->logPath = storage_path('logs/' . $logName);
        $this->maxFiles = $maxFiles;
        $this->initializeLogger($channel);
    }

    private function initializeLogger($channel)
    {
        $this->logger = new Logger('sms');

        // Configure rotating daily files
        $rotatingHandler = new RotatingFileHandler(
            $this->logPath,
            $this->maxFiles,
            Level::Debug
        );

        $formatter = new LineFormatter(
            "[%datetime%] SMS: %message% %context% \n",
            "Y-m-d H:i:s",
            true,
            true
        );

        $rotatingHandler->setFormatter($formatter);
        $this->logger->pushHandler($rotatingHandler);

        // Add useful processors
        // $this->logger->pushProcessor(new IntrospectionProcessor());
        // $this->logger->pushProcessor(new MemoryUsageProcessor());
        // $this->logger->pushProcessor(new ProcessIdProcessor());
    }

    public function log($level, $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }

    // Convenience methods
    public function info($message, array $context = [])
    {
        $this->log(Level::Info, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(Level::Error, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(Level::Warning, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(Level::Debug, $message, $context);
    }
}
