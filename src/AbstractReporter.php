<?php

namespace Lucinda\Framework;

use Lucinda\Logging\Logger;
use Lucinda\STDERR\ErrorType;
use Lucinda\STDERR\Reporter;

/**
 * Bridges STDERR request failures to a Lucinda logger
 *
 * The STDERR error classification determines the logging severity. Client and
 * non-error routes are ignored, while server, syntax, logical, and uncategorized
 * failures are sent to the logger supplied by the application.
 *
 * @see Reporter
 * @see Logger
 */
abstract class AbstractReporter extends Reporter
{
    /**
     * Reports the current STDERR exception at the severity matching its error type
     *
     * ErrorType::SERVER maps to emergency, SYNTAX to alert, LOGICAL to critical,
     * and any remaining failure type to error. NONE and CLIENT are not logged.
     */
    public function run(): void
    {
        $errorType = $this->request->getRoute()->getErrorType();
        if ($errorType === ErrorType::NONE || !$this->isLoggable($errorType)) {
            return;
        }

        $logger = $this->getLogger();
        $exception = $this->request->getException();
        switch ($this->request->getRoute()->getErrorType()) {
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
     * Checks if an ErrorType is suitable for logging (policy to be decided by the extender)
     * 
     * @param ErrorType $errorType
     * @return bool
     */
    protected function isLoggable(ErrorType $errorType): bool
    {
        return true;
    }

    /**
     * Gets the application logger used to persist the current failure
     *
     * @return Logger Configured logger implementation for the desired output medium
     */
    abstract protected function getLogger(): Logger;
}
