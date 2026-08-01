<?php

namespace Lucinda\Framework;

use Lucinda\MVC\Controller;
use Lucinda\STDOUT\MethodNotAllowedException;
use Lucinda\STDOUT\Request;

/**
 * Defines an abstract RESTful controller. Classes extending it must have methods
 * whose name is identical to request methods they are expecting.
 */
abstract class RestController implements Controller
{
    public function __construct(Request $request)
    {
        $methodName = $request->getMethod()->value;
        if (method_exists($this, $methodName)) {
            $this->$methodName();
        } else {
            throw new MethodNotAllowedException($methodName);
        }
    }
}
