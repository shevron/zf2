<?php

/**
 * @namespace
 */
namespace ZendTest\Http\Transport;

use Zend\Http\Transport\Socket as SocketTransport,
    Zend\Http\Request;

class SocketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the correct request method is sent over the wire
     * 
     * @param string $method
     * @dataProvider requestMethodProvider
     */
    public function testCorrectRequestMethod($method)
    {
        $transport = new MockSocketTransport();
        
        $request = new Request();
        $request->setUri('http://localhost/test');
        $request->setMethod($method);

        $transport->setNextResponse($this->getSimpleResponseString());
        $transport->send($request);
        $requestStr = $transport->getLastRequest();
        
        $this->assertRegExp("/^$method /", $requestStr);
    }
    
    /**
     * Test that the correct request URI is sent over the wire
     * 
     * @param string $fullUri
     * @param string $expected
     * @dataProvider requestUriProvider
     */
    public function testCorrectRequestUri($fullUri, $expected)
    {
        $transport = new MockSocketTransport();
        
        $request = new Request();
        $request->setUri($fullUri);
        
        $transport->setNextResponse($this->getSimpleResponseString());
        $transport->send($request);
        $requestStr = $transport->getLastRequest();
        
        $expected = preg_quote($expected, '/');
        $this->assertRegExp("/^GET $expected /", $requestStr);
    }
    
    public function testCorrectHttpVersion()
    {
        $transport = new MockSocketTransport();
        
        $request = new Request();
        $request->setUri('http://localhost/');
        $request->setVersion('1.0');
        
        $transport->setNextResponse($this->getSimpleResponseString());
        $transport->send($request);
        $requestStr = $transport->getLastRequest();
        
        $this->assertRegExp('/^GET \/ HTTP\/1.0\r\n/', $requestStr);
    }
    
    public function testCorrectHeader()
    {
        $headerLine = "X-Foo-Bar: bla bla; version=1.0";
        
        $transport = new MockSocketTransport();
        
        $request = new Request();
        $request->setUri('http://localhost/');
        $request->headers()->addHeaderLine($headerLine);
        
        $transport->setNextResponse($this->getSimpleResponseString());
        $transport->send($request);
        $requestStr = $transport->getLastRequest();
        
        $expected = preg_quote($headerLine, '/');
        $this->assertRegExp("/^$headerLine\r\n/m", $requestStr);
    }
    
    public function testNoBodyRequestEndsWithNlBr()
    {
        $transport = new MockSocketTransport();
        
        $request = new Request();
        $request->setUri('http://localhost/');
        
        $transport->setNextResponse($this->getSimpleResponseString());
        $transport->send($request);
        $requestStr = $transport->getLastRequest();
        
        $this->assertRegExp("/\r\n\r\n$/", $requestStr);
    }
    
    public function testConnectionHeaderIsAddedKeepaliveOn()
    {
        $transport = new MockSocketTransport();
        
        $request = new Request();
        $request->setUri('http://localhost/');
        
        $transport->setNextResponse($this->getSimpleResponseString());
        $transport->send($request);
        $requestStr = $transport->getLastRequest();
        
        $this->assertRegExp("/^Connection: keep-alive\r\n/m", $requestStr);
    }
    
    public function testConnectionHeaderIsAddedKeepaliveOff()
    {
        $transport = new MockSocketTransport(array('keepalive' => false));
        
        $request = new Request();
        $request->setUri('http://localhost/');
        
        $transport->setNextResponse($this->getSimpleResponseString());
        $transport->send($request);
        $requestStr = $transport->getLastRequest();
        
        $this->assertRegExp("/^Connection: close\r\n/m", $requestStr);
    }
    
    /**
     * @dataProvider hostProvider
     */
    public function testHostHeaderIsAdded($url, $expected)
    {
        $transport = new MockSocketTransport();
        
        $request = new Request();
        $request->setUri($url);
        
        $transport->setNextResponse($this->getSimpleResponseString());
        $transport->send($request);
        $requestStr = $transport->getLastRequest();
        
        $expected = preg_quote($expected, "/");
        $this->assertRegExp("/^Host: $expected\r\n/m", $requestStr);
    }
    
    /**
     * Helper functions
     */
    
    protected function getSimpleResponseString()
    {
        return "HTTP/1.1 200 OK\r\n" .
               "Server: not-really-a-server/0.0\r\n" . 
               "Date: " . date(DATE_RFC822) . "\r\n" . 
               "Content-length: 3\r\n" . 
               "Content-type: text/plain\r\n" . 
               "\r\n" . 
               "Hi!";
    }
    
    /**
     * Data Providers
     */
    
    static public function requestMethodProvider()
    {
        return array(
            array('GET'),
            array('DELETE')
        );
    }
    
    static public function requestUriProvider()
    {
        return array(
            array('http://www.example.com/foo/bar', '/foo/bar'),
            array('http://www.example.com/foo/bar?q=foobar&p=grrr', '/foo/bar?q=foobar&p=grrr'),
            array('http://www.example.com/?q=foobar&p=grrr', '/?q=foobar&p=grrr'),
            array('http://www.example.com/foo/bar?q=foobar#fragment', '/foo/bar?q=foobar'),
            array('http://www.example.com', '/'),
        );
    }
    
    static public function hostProvider()
    {
        return array(
            array('http://www.example.com/', 'www.example.com'),
            array('http://www.example.com:80/', 'www.example.com'),
            array('http://www.example.com:82/', 'www.example.com:82'),
            array('https://www.example.com/', 'www.example.com'),
            array('https://www.example.com:443/', 'www.example.com'),
            array('https://www.example.com:80/', 'www.example.com:80'),
        );
    }
}

class MockSocketTransport extends SocketTransport
{
    protected $lastRequest = null;
    
    protected $nextResponse = null;
    
    /**
     * Mock the connection by opening a php://temp socket
     *
     * @param  Zend\Http\Request $request
     * @throws Exception\ConfigurationException
     * @throws Exception\ConnectionException
     */
    protected function connect(Request $request)
    {
        $this->socket = fopen('php://temp', 'r+');
        $this->connectedTo = 'php://temp';
    }
    
    /**
     * Handle mock content before / after sending request
     *
     * @param  Zend\Http\Request $request
     * @throws Exception\ConnectionException
     */
    protected function sendRequest(Request $request)
    {
        parent::sendRequest($request);
        
        // Save last request
        fseek($this->socket, 0);
        $this->lastRequest = stream_get_contents($this->socket);
        
        // Set next response
        $pos = ftell($this->socket);
        fwrite($this->socket, $this->nextResponse);
        fseek($this->socket, $pos);
    }
    
    /**
     * Get last request as string
     * 
     * @return string
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }
    
    /**
     * Set next response from string
     * 
     * @param string $response
     */
    public function setNextResponse($response)
    {
        $this->nextResponse = $response;
    }
    
    /**
     * Reset the stream - close it an open a new temp stream
     * 
     */
    public function resetStream()
    {
        fclose($this->socket);
        $this->socket = fopen('php://temp', 'r+');
    }
}