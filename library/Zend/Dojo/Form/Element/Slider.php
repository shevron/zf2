<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Dojo
 */

namespace Zend\Dojo\Form\Element;

/**
 * Abstract Slider dijit
 *
 * @package    Zend_Dojo
 * @subpackage Form_Element
 */
abstract class Slider extends Dijit
{
    /**
     * Set clickSelect flag
     *
     * @param  bool $clickSelect
     * @return \Zend\Dojo\Form\Element\TextBox
     */
    public function setClickSelect($flag)
    {
        $this->setDijitParam('clickSelect', (bool) $flag);
        return $this;
    }

    /**
     * Retrieve clickSelect flag
     *
     * @return bool
     */
    public function getClickSelect()
    {
        if (!$this->hasDijitParam('clickSelect')) {
            return false;
        }
        return $this->getDijitParam('clickSelect');
    }

    /**
     * Set intermediateChanges flag
     *
     * @param  bool $intermediateChanges
     * @return \Zend\Dojo\Form\Element\TextBox
     */
    public function setIntermediateChanges($flag)
    {
        $this->setDijitParam('intermediateChanges', (bool) $flag);
        return $this;
    }

    /**
     * Retrieve intermediateChanges flag
     *
     * @return bool
     */
    public function getIntermediateChanges()
    {
        if (!$this->hasDijitParam('intermediateChanges')) {
            return false;
        }
        return $this->getDijitParam('intermediateChanges');
    }

    /**
     * Set showButtons flag
     *
     * @param  bool $showButtons
     * @return \Zend\Dojo\Form\Element\TextBox
     */
    public function setShowButtons($flag)
    {
        $this->setDijitParam('showButtons', (bool) $flag);
        return $this;
    }

    /**
     * Retrieve showButtons flag
     *
     * @return bool
     */
    public function getShowButtons()
    {
        if (!$this->hasDijitParam('showButtons')) {
            return false;
        }
        return $this->getDijitParam('showButtons');
    }

    /**
     * Set discreteValues
     *
     * @param  int $value
     * @return \Zend\Dojo\Form\Element\TextBox
     */
    public function setDiscreteValues($value)
    {
        $this->setDijitParam('discreteValues', (int) $value);
        return $this;
    }

    /**
     * Retrieve discreteValues
     *
     * @return int|null
     */
    public function getDiscreteValues()
    {
        return $this->getDijitParam('discreteValues');
    }

    /**
     * Set maximum
     *
     * @param  int $value
     * @return \Zend\Dojo\Form\Element\TextBox
     */
    public function setMaximum($value)
    {
        $this->setDijitParam('maximum', (int) $value);
        return $this;
    }

    /**
     * Retrieve maximum
     *
     * @return int|null
     */
    public function getMaximum()
    {
        return $this->getDijitParam('maximum');
    }

    /**
     * Set minimum
     *
     * @param  int $value
     * @return \Zend\Dojo\Form\Element\TextBox
     */
    public function setMinimum($value)
    {
        $this->setDijitParam('minimum', (int) $value);
        return $this;
    }

    /**
     * Retrieve minimum
     *
     * @return int|null
     */
    public function getMinimum()
    {
        return $this->getDijitParam('minimum');
    }

    /**
     * Set pageIncrement
     *
     * @param  int $value
     * @return \Zend\Dojo\Form\Element\TextBox
     */
    public function setPageIncrement($value)
    {
        $this->setDijitParam('pageIncrement', (int) $value);
        return $this;
    }

    /**
     * Retrieve pageIncrement
     *
     * @return int|null
     */
    public function getPageIncrement()
    {
        return $this->getDijitParam('pageIncrement');
    }
}
