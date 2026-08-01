<?php

namespace Lucinda\Framework;

use Lucinda\Logging\Logger;
use Lucinda\STDERR\ErrorType;
use Lucinda\STDERR\Reporter;

/**
 * Binds STDERR MVC API with Logging API for error reporting
 */
abstract class AbstractReporter extends Reporter
{
    /**
     * {@inheritDoc}
     *
     * @see Runnable::run()
     */
    public function run(): void
    {
        $logger = $this->getLogger();
        $exception = $this->request->getException();
        switch ($this->request->getRoute()->getErrorType()) {
        case ErrorType::NONE:
        case ErrorType::CLIENT:
            break;
        case ErrorType::SERVER:
            $logger->emergency($exception);
            break;
        case ErrorType::SYNTAX:
            $logger->alert($exception);
            break;
        case ErrorType::LOGICAL:
            $logger->critical($exception);
            break;
        default:
            $logger->error($exception);
            break;
        }
    }

    /**
     * Gets instance of logger that will report error to a medium
     *
     * @return Logger
     */
    abstract protected function getLogger(): Logger;
}
