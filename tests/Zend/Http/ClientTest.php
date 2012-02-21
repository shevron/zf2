<?php

/**
 * @namespace
 */
namespace ZendTest\Http;

use Zend\Http\Client,
    Zend\Http\Transport\Test as TestTransport,
    Zend\Http\Transport\Socket as SocketTransport;

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
     * @var Zend\Http\Transport\Transport
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
}
