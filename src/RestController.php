<?php

namespace Lucinda\Framework;

use Lucinda\MVC\Runnable;
use Lucinda\STDOUT\Controller;
use Lucinda\STDOUT\MethodNotAllowedException;

/**
 * Defines an abstract RESTful controller. Classes extending it must have methods
 * whose name is identical to request methods they are expecting.
 */
abstract class RestController extends Controller
{
    /**
     * {@inheritDoc}
     * @throws MethodNotAllowedException
     * @see Runnable::run()
     */
    public function run(): void
    {
        $methodName = $this->request->getMethod()->value;
        if (method_exists($this, $methodName)) {
            $this->$methodName();
        } else {
            throw new MethodNotAllowedException();
        }
    }
}
