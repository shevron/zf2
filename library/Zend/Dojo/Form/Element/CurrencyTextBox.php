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

use Zend\Form\Element\Exception;

/**
 * CurrencyTextBox dijit
 *
 * @package    Zend_Dojo
 * @subpackage Form_Element
 */
class CurrencyTextBox extends NumberTextBox
{
    /**
     * Use CurrencyTextBox dijit view helper
     * @var string
     */
    public $helper = 'CurrencyTextBox';

    /**
     * Set currency
     *
     * @param  string $currency
     * @return \Zend\Dojo\Form\Element\CurrencyTextBox
     */
    public function setCurrency($currency)
    {
        $this->setDijitParam('currency', (string) $currency);
        return $this;
    }

    /**
     * Retrieve currency
     *
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->getDijitParam('currency');
    }

    /**
     * Set currency symbol
     *
     * Casts to string, uppercases, and trims to three characters.
     *
     * @param  string $symbol
     * @return \Zend\Dojo\Form\Element\CurrencyTextBox
     */
    public function setSymbol($symbol)
    {
        $symbol = strtoupper((string) $symbol);
        $length = strlen($symbol);
        if (3 > $length) {
            throw new Exception\InvalidArgumentException('Invalid symbol provided; please provide ISO 4217 alphabetic currency code');
        }
        if (3 < $length) {
            $symbol = substr($symbol, 0, 3);
        }

        $this->setConstraint('symbol', $symbol);
        return $this;
    }

    /**
     * Retrieve symbol
     *
     * @return string|null
     */
    public function getSymbol()
    {
        return $this->getConstraint('symbol');
    }

    /**
     * Set whether currency is fractional
     *
     * @param  bool $flag
     * @return \Zend\Dojo\Form\Element\CurrencyTextBox
     */
    public function setFractional($flag)
    {
        $this->setConstraint('fractional', (bool) $flag);
        return $this;
    }

    /**
     * Get whether or not to present fractional values
     *
     * @return bool
     */
    public function getFractional()
    {
        return ('true' == $this->getConstraint('fractional'));
    }
}
