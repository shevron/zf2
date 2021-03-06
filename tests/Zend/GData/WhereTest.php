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
class WhereTest extends \PHPUnit_Framework_TestCase
{

    public function setUp() {
        $this->whereText = file_get_contents(
                'Zend/GData/_files/WhereElementSample1.xml',
                true);
        $this->where = new Extension\Where();
    }

    public function testEmptyWhereShouldHaveNoExtensionElements() {
        $this->assertTrue(is_array($this->where->extensionElements));
        $this->assertTrue(count($this->where->extensionElements) == 0);
    }

    public function testEmptyWhereShouldHaveNoExtensionAttributes() {
        $this->assertTrue(is_array($this->where->extensionAttributes));
        $this->assertTrue(count($this->where->extensionAttributes) == 0);
    }

    public function testSampleWhereShouldHaveNoExtensionElements() {
        $this->where->transferFromXML($this->whereText);
        $this->assertTrue(is_array($this->where->extensionElements));
        $this->assertTrue(count($this->where->extensionElements) == 0);
    }

    public function testSampleWhereShouldHaveNoExtensionAttributes() {
        $this->where->transferFromXML($this->whereText);
        $this->assertTrue(is_array($this->where->extensionAttributes));
        $this->assertTrue(count($this->where->extensionAttributes) == 0);
    }

    public function testNormalWhereShouldHaveNoExtensionElements() {
        $this->where->valueString = "Test Value String";
        $this->where->rel = "http://schemas.google.com/g/2005#event.alternate";
        $this->where->label = "Test Label";

        $this->assertEquals("Test Value String", $this->where->valueString);
        $this->assertEquals("http://schemas.google.com/g/2005#event.alternate", $this->where->rel);
        $this->assertEquals("Test Label", $this->where->label);

        $this->assertEquals(0, count($this->where->extensionElements));
        $newWhere = new Extension\Where();
        $newWhere->transferFromXML($this->where->saveXML());
        $this->assertEquals(0, count($newWhere->extensionElements));
        $newWhere->extensionElements = array(
                new \Zend\GData\App\Extension\Element('foo', 'atom', null, 'bar'));
        $this->assertEquals(1, count($newWhere->extensionElements));
        $this->assertEquals("Test Value String", $newWhere->valueString);
        $this->assertEquals("http://schemas.google.com/g/2005#event.alternate", $newWhere->rel);
        $this->assertEquals("Test Label", $newWhere->label);

        /* try constructing using magic factory */
        $gdata = new \Zend\GData\GData();
        $newWhere2 = $gdata->newWhere();
        $newWhere2->transferFromXML($newWhere->saveXML());
        $this->assertEquals(1, count($newWhere2->extensionElements));
        $this->assertEquals("Test Value String", $newWhere2->valueString);
        $this->assertEquals("http://schemas.google.com/g/2005#event.alternate", $newWhere2->rel);
        $this->assertEquals("Test Label", $newWhere2->label);
    }

    public function testEmptyWhereToAndFromStringShouldMatch() {
        $whereXml = $this->where->saveXML();
        $newWhere = new Extension\Where();
        $newWhere->transferFromXML($whereXml);
        $newWhereXml = $newWhere->saveXML();
        $this->assertTrue($whereXml == $newWhereXml);
    }

    public function testWhereWithValueToAndFromStringShouldMatch() {
        $this->where->valueString = "Test Value String";
        $this->where->rel = "http://schemas.google.com/g/2005#event.alternate";
        $this->where->label = "Test Label";
        $whereXml = $this->where->saveXML();
        $newWhere = new Extension\Where();
        $newWhere->transferFromXML($whereXml);
        $newWhereXml = $newWhere->saveXML();
        $this->assertTrue($whereXml == $newWhereXml);
        $this->assertEquals("Test Value String", $this->where->valueString);
        $this->assertEquals("http://schemas.google.com/g/2005#event.alternate", $this->where->rel);
        $this->assertEquals("Test Label", $this->where->label);
    }

    public function testExtensionAttributes() {
        $extensionAttributes = $this->where->extensionAttributes;
        $extensionAttributes['foo1'] = array('name'=>'foo1', 'value'=>'bar');
        $extensionAttributes['foo2'] = array('name'=>'foo2', 'value'=>'rab');
        $this->where->extensionAttributes = $extensionAttributes;
        $this->assertEquals('bar', $this->where->extensionAttributes['foo1']['value']);
        $this->assertEquals('rab', $this->where->extensionAttributes['foo2']['value']);
        $whereXml = $this->where->saveXML();
        $newWhere = new Extension\Where();
        $newWhere->transferFromXML($whereXml);
        $this->assertEquals('bar', $newWhere->extensionAttributes['foo1']['value']);
        $this->assertEquals('rab', $newWhere->extensionAttributes['foo2']['value']);
    }

    public function testConvertFullWhereToAndFromString() {
        $this->where->transferFromXML($this->whereText);
        $this->assertEquals("Joe's Pub", $this->where->valueString);
        $this->assertEquals("http://schemas.google.com/g/2005#event", $this->where->rel);
        $this->assertEquals("1234 Anywhere Ln., New York, NY", $this->where->label);
        $this->assertTrue($this->where->entryLink instanceof Extension\EntryLink);
        $this->assertEquals("http://local.example.com/10018/JoesPub", $this->where->entryLink->href);
    }

}
