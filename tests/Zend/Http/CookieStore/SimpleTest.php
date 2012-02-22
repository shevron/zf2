<?php

/**
 * @namespace
 */
namespace ZendTest\Http\Transport;

use Zend\Http\CookieStore\Simple as SimpleCookieStore,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Http\Header\Cookie;

class SimpleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Cookie store
     *
     * @var Zend\Http\CookieStore\Simple
     */
    protected $cs = null;

    public function setUp()
    {
        $this->cs = new SimpleCookieStore();
    }

    public function tearDown()
    {
        $this->cs = null;
    }

    public function testAddGetSingleCookie()
    {
        $this->cs->addCookie('foo', 'bar', 'example.com');
        $cookies = $this->cs->getMatchingCookies('http://example.com/');
        $this->assertEquals(1, count($cookies));
        $this->assertEquals('foo=bar', Cookie::fromSetCookieArray($cookies)->getFieldValue());
    }

    public function testAddGetNonMatchingCookie()
    {
        $this->cs->addCookie('foo', 'bar', 'example.com');
        $cookies = $this->cs->getMatchingCookies('http://rexample.com/');
        $this->assertEquals(0, count($cookies));
    }

    public function testAddGetMultipleCookiesSubdomainMatch()
    {
        $this->cs->addCookie('foo1', 'bar1', 'example.com');
        $this->cs->addCookie('foo2', 'bar2', 'www.example.com');
        $this->cs->addCookie('foo3', 'bar3', 'other.example.com');
        $this->cs->addCookie('foo4', 'bar4', 'www.other.com');

        $cookies = $this->cs->getMatchingCookies('http://www.example.com/');

        $this->assertEquals(2, count($cookies));
        $this->assertEquals('foo1=bar1; foo2=bar2', Cookie::fromSetCookieArray($cookies)->getFieldValue());
    }

    public function testAddGetMultipleCookiesPathMatch()
    {
        $this->cs->addCookie('foo1', 'bar1', 'example.com');
        $this->cs->addCookie('foo2', 'bar2', 'example.com', null, '/foo');
        $this->cs->addCookie('foo3', 'bar3', 'example.com', null, '/foo/bar');
        $this->cs->addCookie('foo4', 'bar4', 'example.com', null, '/foo/bar/baz');
        $this->cs->addCookie('foo5', 'bar5', 'example.com', null, '/baz');

        $cookies = $this->cs->getMatchingCookies('http://example.com/foo/bar');

        $this->assertEquals(3, count($cookies));
        $this->assertEquals('foo1=bar1; foo2=bar2; foo3=bar3', Cookie::fromSetCookieArray($cookies)->getFieldValue());
    }
}