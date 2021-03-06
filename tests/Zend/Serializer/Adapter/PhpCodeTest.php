<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Serializer
 */

namespace ZendTest\Serializer\Adapter;

use Zend\Serializer;
use Zend\Serializer\Exception;

/**
 * @category   Zend
 * @package    Zend_Serializer
 * @subpackage UnitTests
 * @group      Zend_Serializer
 */
class PhpCodeTest extends \PHPUnit_Framework_TestCase
{

    private $_adapter;

    public function setUp()
    {
        $this->_adapter = new Serializer\Adapter\PhpCode();
    }

    public function tearDown()
    {
        $this->_adapter = null;
    }

    public function testSerializeString()
    {
        $value      = 'test';
        $expected   = "'test'";

        $data = $this->_adapter->serialize($value);
        $this->assertEquals($expected, $data);
    }

    public function testSerializeFalse()
    {
        $value    = false;
        $expected = 'false';

        $data = $this->_adapter->serialize($value);
        $this->assertEquals($expected, $data);
    }

    public function testSerializeNull()
    {
        $value    = null;
        $expected = 'NULL';

        $data = $this->_adapter->serialize($value);
        $this->assertEquals($expected, $data);
    }

    public function testSerializeNumeric()
    {
        $value    = 100.12345;
        $expected = '100.12345';

        $data = $this->_adapter->serialize($value);
        $this->assertEquals($expected, $data);
    }

    public function testSerializeObject()
    {
        $value    = new \stdClass();
        $expected = "stdClass::__set_state(array(\n))";

        $data = $this->_adapter->serialize($value);
        $this->assertEquals($expected, $data);
    }

    public function testUnserializeString()
    {
        $value    = "'test'";
        $expected = 'test';

        $data = $this->_adapter->unserialize($value);
        $this->assertEquals($expected, $data);
    }

    public function testUnserializeFalse()
    {
        $value    = 'false';
        $expected = false;

        $data = $this->_adapter->unserialize($value);
        $this->assertEquals($expected, $data);
    }

    public function testUnserializeNull()
    {
        $value    = 'NULL';
        $expected = null;

        $data = $this->_adapter->unserialize($value);
        $this->assertEquals($expected, $data);
    }

    public function testUnserializeNumeric()
    {
        $value    = '100';
        $expected = 100;

        $data = $this->_adapter->unserialize($value);
        $this->assertEquals($expected, $data);
    }

/* TODO: PHP Fatal error:  Call to undefined method stdClass::__set_state()
    public function testUnserializeObject()
    {
        $value    = "stdClass::__set_state(array(\n))";
        $expected = new stdClass();

        $data = $this->_adapter->unserialize($value);
        $this->assertEquals($expected, $data);
    }
*/

    public function testUnserializeInvalid()
    {
        $value = 'not a serialized string';
        
        $this->setExpectedException('Zend\Serializer\Exception\RuntimeException', 'eval failed: syntax error');
        $this->_adapter->unserialize($value);
    }

}
