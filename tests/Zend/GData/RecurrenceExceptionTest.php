<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_GData
 */

namespace ZendTest\GData;
use Zend\GData\Extension;

/**
 * @category   Zend
 * @package    Zend_GData
 * @subpackage UnitTests
 * @group      Zend_GData
 */
class RecurrenceExceptionTest extends \PHPUnit_Framework_TestCase
{

    public function setUp() {
        $this->recurrenceExceptionText = file_get_contents(
                'Zend/GData/_files/RecurrenceExceptionElementSample1.xml',
                true);
        $this->recurrenceException = new Extension\RecurrenceException();
    }

    public function testEmptyRecurrenceExceptionShouldHaveNoExtensionElements() {
        $this->assertTrue(is_array($this->recurrenceException->extensionElements));
        $this->assertTrue(count($this->recurrenceException->extensionElements) == 0);
    }

    public function testEmptyRecurrenceExceptionShouldHaveNoExtensionAttributes() {
        $this->assertTrue(is_array($this->recurrenceException->extensionAttributes));
        $this->assertTrue(count($this->recurrenceException->extensionAttributes) == 0);
    }

    public function testSampleRecurrenceExceptionShouldHaveNoExtensionElements() {
        $this->recurrenceException->transferFromXML($this->recurrenceExceptionText);
        $this->assertTrue(is_array($this->recurrenceException->extensionElements));
        $this->assertTrue(count($this->recurrenceException->extensionElements) == 0);
    }

    public function testSampleRecurrenceExceptionShouldHaveNoExtensionAttributes() {
        $this->recurrenceException->transferFromXML($this->recurrenceExceptionText);
        $this->assertTrue(is_array($this->recurrenceException->extensionAttributes));
        $this->assertTrue(count($this->recurrenceException->extensionAttributes) == 0);
    }

    public function testNormalRecurrenceExceptionShouldHaveNoExtensionElements() {
        $this->recurrenceException->specialized = "false";

        $this->assertEquals("false", $this->recurrenceException->specialized);

        $this->assertEquals(0, count($this->recurrenceException->extensionElements));
        $newRecurrenceException = new Extension\RecurrenceException();
        $newRecurrenceException->transferFromXML($this->recurrenceException->saveXML());
        $this->assertEquals(0, count($newRecurrenceException->extensionElements));
        $newRecurrenceException->extensionElements = array(
                new \Zend\GData\App\Extension\Element('foo', 'atom', null, 'bar'));
        $this->assertEquals(1, count($newRecurrenceException->extensionElements));
        $this->assertEquals("false", $newRecurrenceException->specialized);

        /* try constructing using magic factory */
        $gdata = new \Zend\GData\GData();
        $newRecurrenceException2 = $gdata->newRecurrenceException();
        $newRecurrenceException2->transferFromXML($newRecurrenceException->saveXML());
        $this->assertEquals(1, count($newRecurrenceException2->extensionElements));
        $this->assertEquals("false", $newRecurrenceException2->specialized);
    }

    public function testEmptyRecurrenceExceptionToAndFromStringShouldMatch() {
        $recurrenceExceptionXml = $this->recurrenceException->saveXML();
        $newRecurrenceException = new Extension\RecurrenceException();
        $newRecurrenceException->transferFromXML($recurrenceExceptionXml);
        $newRecurrenceExceptionXml = $newRecurrenceException->saveXML();
        $this->assertTrue($recurrenceExceptionXml == $newRecurrenceExceptionXml);
    }

    public function testRecurrenceExceptionWithValueToAndFromStringShouldMatch() {
        $this->recurrenceException->specialized = "false";
        $recurrenceExceptionXml = $this->recurrenceException->saveXML();
        $newRecurrenceException = new Extension\RecurrenceException();
        $newRecurrenceException->transferFromXML($recurrenceExceptionXml);
        $newRecurrenceExceptionXml = $newRecurrenceException->saveXML();
        $this->assertTrue($recurrenceExceptionXml == $newRecurrenceExceptionXml);
        $this->assertEquals("false", $this->recurrenceException->specialized);
    }

    public function testExtensionAttributes() {
        $extensionAttributes = $this->recurrenceException->extensionAttributes;
        $extensionAttributes['foo1'] = array('name'=>'foo1', 'value'=>'bar');
        $extensionAttributes['foo2'] = array('name'=>'foo2', 'value'=>'rab');
        $this->recurrenceException->extensionAttributes = $extensionAttributes;
        $this->assertEquals('bar', $this->recurrenceException->extensionAttributes['foo1']['value']);
        $this->assertEquals('rab', $this->recurrenceException->extensionAttributes['foo2']['value']);
        $recurrenceExceptionXml = $this->recurrenceException->saveXML();
        $newRecurrenceException = new Extension\RecurrenceException();
        $newRecurrenceException->transferFromXML($recurrenceExceptionXml);
        $this->assertEquals('bar', $newRecurrenceException->extensionAttributes['foo1']['value']);
        $this->assertEquals('rab', $newRecurrenceException->extensionAttributes['foo2']['value']);
    }

    public function testConvertFullRecurrenceExceptionToAndFromString() {
        $this->recurrenceException->transferFromXML($this->recurrenceExceptionText);
        $this->assertEquals("true", $this->recurrenceException->specialized);
        $this->assertTrue($this->recurrenceException->entryLink instanceof Extension\EntryLink);
        $this->assertEquals("http://www.google.com/calendar/feeds/default/private/full/hj4geu9lpkh3ebk6rvm4k8mhik", $this->recurrenceException->entryLink->href);
        $this->assertTrue($this->recurrenceException->originalEvent instanceof Extension\OriginalEvent);
        $this->assertEquals("hj4geu9lpkh3ebk6rvm4k8mhik", $this->recurrenceException->originalEvent->id);
    }

}
