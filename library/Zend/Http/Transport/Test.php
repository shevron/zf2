<?php

namespace Zend\Http\Transport;

use Zend\Http\Request,
    Zend\Http\Response;

class Test implements Transport
{
    /**
     * Response queue
     *
     * @var \SplQueue
     */
    protected $responseQueue;

    /**
     * The default HTTP response returned if there are no responses in the queue
     *
     * @var Zend\Http\Response
     */
    protected $defaultResponse;

    /**
     * Send request
     *
     * This will return the pre-defined response object
     *
     * @see Zend\Http\Transport\Transport::send()
     */
    public function send(Request $request, Response $response = null)
    {
        if (count($this->getResponseQueue())) {
            return $this->getResponseQueue()->dequeue();
        } elseif ($this->defaultResponse) {
            return $this->defaultResponse;
        } elseif ($response) {
            return $response;
        } else {
            throw new Exception\ConfigurationException("Response queue is empty and no default response has been defined");
        }
    }

    /**
     * Send transport adapter configuration
     *
     * @param array $config
     * @return \Zend\Http\Transport\Transport
     */
    public function setConfig(array $config = array())
    {

    }

    /**
     * Set the response queue object
     *
     * @param \SplQueue $queue
     */
    public function setResponseQueue(\SplQueue $queue)
    {
        $this->responseQueue = $queue;
    }

    /**
     * Get the response queue object
     *
     * @return \SplQueue
     */
    public function getResponseQueue()
    {
        if (! $this->responseQueue) {
            $this->responseQueue = new \SplQueue();
        }

        return $this->responseQueue;
    }

    /**
     * Set the default response object.
     *
     * This object will be returned when there are no responses in the queue
     *
     * @param Zend\Http\Response $response
     */
    public function setDefaultResponse(Response $response)
    {
        $this->defaultResponse = $response;
    }

    /**
     * Get the default response object, or null if none was set
     *
     * @return Zend\Http\Response
     */
    public function getDefaultResponse()
    {
        return $this->defaultResponse;
    }
}