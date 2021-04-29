<?php
namespace Lucinda\Framework;

/**
 * Encapsulates blueprint of reporting to a Lucinda\Logging\Logger
 */
abstract class AbstractReporter extends \Lucinda\STDERR\Reporter
{
    /**
     * {@inheritDoc}
     * @see \Lucinda\MVC\Runnable::run()
     */
    public function run(): void
    {
        $logger = $this->getLogger();
        $exception = $this->request->getException();
        switch ($this->request->getRoute()->getErrorType()) {
            case \Lucinda\STDERR\ErrorType::NONE:
            case \Lucinda\STDERR\ErrorType::CLIENT:
                break;
            case \Lucinda\STDERR\ErrorType::SERVER:
                $logger->emergency($exception);
                break;
            case \Lucinda\STDERR\ErrorType::SYNTAX:
                $logger->alert($exception);
                break;
            case \Lucinda\STDERR\ErrorType::LOGICAL:
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
     * @return \Lucinda\Logging\Logger
     */
    abstract protected function getLogger(): \Lucinda\Logging\Logger;
}
