<?php

/**
 * @namespace
 */
namespace ZendTest\Http;

use Zend\Http\Client,
    Zend\Http\Transport\Test as TestTransport,
    Zend\Http\Transport\Socket as SocketTransport,
    Zend\Http\Request,
    Zend\Http\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * HTTP client object
     *
     * @var Zend\Http\Client
     */
    protected $client = null;

    /**
     * HTTP transport object
     *
     * @var Zend\Http\Transport\Test
     */
    protected $transport = null;

    public function setUp()
    {
        $this->transport = new TestTransport();
        $this->client = new Client();
        $this->client->setTransport($this->transport);
    }

    public function tearDown()
    {
        unset($this->client);
        unset($this->transport);
    }

    public function testDefaultTransportIsSocket()
    {
        $client = new Client();
        $this->assertTrue($client->getTransport() instanceof SocketTransport);
    }

    public function testSetGetTransport()
    {
        $this->client->setTransport($this->transport);
        $this->assertSame($this->transport, $this->client->getTransport());
    }

    public function testRedirectCountIsZero()
    {
        $this->assertEquals(0, $this->client->getRedirectionsCount());
    }

    public function testRedirectionCountIncremented()
    {
        $respQueue = $this->transport->getResponseQueue();
        $respQueue->enqueue(Response::fromString("HTTP/1.1 301 Moved Permanently\r\nLocation: /otherUrl\r\n\r\n"));
        $respQueue->enqueue(Response::fromString("HTTP/1.1 301 Moved Permanently\r\nLocation: /oneMoreLocation\r\n\r\n"));
        $respQueue->enqueue(Response::fromString("HTTP/1.1 200 Ok\r\nContent-length: 0\r\n\r\n"));

        $request = new Request();
        $request->setUri("http://www.example.com/");
        $resp = $this->client->send($request);

        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals(2, $this->client->getRedirectionsCount());
    }

    public function testRedirectionLimit()
    {
        $this->client->setConfig(array('maxredirects' => 2));

        $respQueue = $this->transport->getResponseQueue();
        $respQueue->enqueue(Response::fromString("HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\n\r\n"));
        $respQueue->enqueue(Response::fromString("HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect2\r\n\r\n"));
        $respQueue->enqueue(Response::fromString("HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect3\r\n\r\n"));
        $respQueue->enqueue(Response::fromString("HTTP/1.1 200 Ok\r\nContent-length: 0\r\n\r\n"));

        $request = new Request();
        $request->setUri("http://www.example.com/");
        $resp = $this->client->send($request);

        $this->assertEquals(301, $resp->getStatusCode());
        $this->assertEquals("/redirect3", $this->client->headers()->get('Location')->getFieldValue());
    }
}
