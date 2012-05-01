<?php

namespace ZendTest\Http;

use Zend\Http\Client,
    Zend\Http\ClientOptions,
    Zend\Http\Transport\Options as TransportOptions,
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
        $this->transport->setDefaultResponse(Response::fromString("HTTP/1.1 200 Ok\r\nContent-length: 0\r\n\r\n"));

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

        $request = new Request();
        $request->setUri("http://www.example.com/");
        $resp = $this->client->send($request);

        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals(2, $this->client->getRedirectionsCount());
    }

    public function testRedirectionLimit()
    {
        $this->client->setOptions(new ClientOptions(array('maxredirects' => 2)));

        $respQueue = $this->transport->getResponseQueue();
        $respQueue->enqueue(Response::fromString("HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect1\r\n\r\n"));
        $respQueue->enqueue(Response::fromString("HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect2\r\n\r\n"));
        $respQueue->enqueue(Response::fromString("HTTP/1.1 301 Moved Permanently\r\nLocation: /redirect3\r\n\r\n"));

        $request = new Request();
        $request->setUri("http://www.example.com/");
        $resp = $this->client->send($request);

        $this->assertEquals(301, $resp->getStatusCode());
        $this->assertEquals("/redirect3", $resp->headers()->get('Location')->getFieldValue());
    }

    public function testSettingGlobalHeader()
    {
        $uaString = "MyHttpClient\1.1";
        $this->client->headers()->addHeaderLine("User-agent: $uaString");

        $request = new Request();
        $request->setUri("http://www.example.com/");
        $this->assertFalse($request->headers()->has('User-agent'));

        $resp = $this->client->send($request);

        $this->assertEquals($uaString, $request->headers()->get('User-agent')->getFieldValue());
    }

    public function testSettingGlobalHeaderDoesntOverrideLocalHeader()
    {
        $uaString = "OtherHttpClient\1.0";
        $this->client->headers()->addHeaderLine("User-agent: MyHttpClient\1.1");

        $request = Request::fromString("GET / HTTP/1.1\r\nUser-agent: $uaString\r\n\r\n");
        $request->setUri('http://www.example.com/');
        $this->assertTrue($request->headers()->has('User-agent'));

        $resp = $this->client->send($request);

        $this->assertEquals($uaString, $request->headers()->get('User-agent')->getFieldValue());
    }

    public function testSetGetOptions()
    {
        $options = new ClientOptions();
        $this->client->setOptions($options);
        $this->assertSame($options, $this->client->getOptions());
    }

    public function testSetOptionPassesTransportOptions()
    {
        $options = new ClientOptions();
        $transportOptions = new TransportOptions(array('sslVerifyPeer' => false));
        $options->setTransportOptions($transportOptions);

        $this->client->setOptions($options);
        $this->assertSame($transportOptions, $this->transport->getOptions());
        $this->assertFalse($this->transport->getOptions()->getSslVerifyPeer());
    }

    public function testSetOptionPassesTransportOptionsAsArray()
    {
        $options = new ClientOptions(array(
            'transportOptions' => new TransportOptions(array('sslVerifyPeer' => false))
        ));

        $this->client->setOptions($options);

        $this->assertFalse($this->transport->getOptions()->getSslVerifyPeer());
    }

    public function testSetOptionsPassesTransportOptionsImplicitlyAtSend()
    {
        $client = new MockClient();
        $options = new ClientOptions(array(
            'transportOptions' => new TransportOptions(array('sslVerifyPeer' => false))
        ));

        $client->setOptions($options);
        $client->getTransport()->getResponseQueue()->enqueue(
            Response::fromString("HTTP/1.1 200 Ok\r\nContent-length: 0\r\n\r\n")
        );

        $request = Request::fromString("GET / HTTP/1.1\r\nUser-agent: foobarclient\r\n\r\n");
        $request->setUri('http://www.example.com/');
        $response = $client->send($request);

        $this->assertFalse($client->getTransport()->getOptions()->getSslVerifyPeer());
    }
}

class MockClient extends Client
{
    static protected $defaultTransport = 'Zend\Http\Transport\Test';
}
