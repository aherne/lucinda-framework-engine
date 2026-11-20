<?php

namespace Lucinda\Framework;

use Lucinda\Logging\Logger;
use Lucinda\STDERR\ErrorType;
use Lucinda\STDERR\ErrorReporter;

/**
 * Bridges STDERR request failures to a Lucinda logger
 *
 * Subclasses decide which classifications are loggable and supply their logger.
 * Accepted failures are dispatched at the severity selected below; an INTERNAL
 * failure can additionally report its preceding exception.
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
     * CLIENT maps to error and INTERNAL maps to emergency. NONE emits no entry.
     * An INTERNAL failure also logs its preceding exception when one is supplied.
     *
     * @param Lucinda\STDERR\ErrorType $errorType Value supplied by the caller.
     * @param Throwable $error Value supplied by the caller.
     * @param ?Throwable $previous Value supplied by the caller.
     * @return void
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

        if ($errorType === ErrorType::INTERNAL && $previous!==null) {
            $logger->emergency($previous);
        }
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
