<?php

namespace Zend\Http\Transport;

use Zend\Http\RequestPool;

interface MultiRequest extends Transport
{
    /**
     * Send a pool of HTTP requests concurrently
     *
     * Returns an array of responses corresponding to the provided pool of
     * requests. If provided, $responseClass will be used as the class for each
     * response object. Otherwise, the default response class will be used.
     *
     * @param  Zend\Http\RequestPool    $request
     * @param  Zend\Http\Reponse|string $responseClass
     * @return array
     */
    public function sendMulti(RequestPool $requestPool);
}