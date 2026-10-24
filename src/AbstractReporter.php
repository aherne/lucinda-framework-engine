<?php

namespace Lucinda\Framework;

use Lucinda\Logging\Logger;
use Lucinda\STDERR\ErrorType;
use Lucinda\STDERR\ErrorReporter;

/**
 * Bridges STDERR request failures to a Lucinda logger
 *
 * The STDERR error classification determines the logging severity. Client and
 * non-error routes are ignored, while server, syntax, logical, and uncategorized
 * failures are sent to the logger supplied by the application.
 *
 * @see ErrorReporter
 * @see Logger
 */
abstract class AbstractReporter implements ErrorReporter
{
    /**
     * Reports the current STDERR exception at the severity matching its error type
     *
     * ErrorType::SERVER maps to emergency, SYNTAX to alert, LOGICAL to critical,
     * and any remaining failure type to error. NONE and CLIENT are not logged.
     */
    public function report(ErrorType $errorType, \Throwable $error, ?\Throwable $previous = null): void {
        if (!$this->isLoggable($errorType)) {
            return;
        }

        $logger = $this->getLogger($errorType);

        match ($errorType) {
            ErrorType::NONE => null,
            ErrorType::SERVER => $logger->emergency($error),
            ErrorType::SYNTAX => $logger->alert($error),
            ErrorType::LOGICAL => $logger->critical($error),
            ErrorType::CLIENT => $logger->error($error),
            ErrorType::INTERNAL => $logger->emergency($error)
        };
    }

    /**
     * Checks if an ErrorType is suitable for logging (policy to be decided by the extender)
     * 
     * @param ErrorType $errorType
     * @return bool
     */
    abstract protected function isLoggable(ErrorType $errorType): bool;

    /**
     * Gets the application logger used to persist the current failure
     *
     * @param ErrorType $errorType
     * @return Logger Configured logger implementation for the desired output medium
     */
    abstract protected function getLogger(ErrorType $errorType): Logger;
}
