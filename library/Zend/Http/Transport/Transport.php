<?php

namespace Zend\Http\Transport;

use Zend\Http\Request,
    Zend\Http\Response;

interface Transport
{
    /**
     * Send HTTP request
     *
     * You can optionally provide a response object to be populated. If none
     * provided, the transport will create a default response object and return
     * it
     *
     * @param  Zend\Http\Request        $request
     * @param  Zend\Http\Reponse|string $response
     * @return Zend\Http\Response
     */
    public function send(Request $request, Response $response = null);

    /**
     * Set configuration of transport adapter
     *
     * @param array $config
     * @return Zend\Http\Transport\Transport
     */
    public function setConfig(array $config = array());
}