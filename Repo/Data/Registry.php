<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Repo\Entity\Data;

/**
 * Registry for pension fund customers.
 */
class Registry
    extends \Praxigento\Core\App\Repo\Data\Entity\Base
{
    const ATTR_AMOUNT_IN = 'amount_in';
    const ATTR_AMOUNT_PERCENT = 'amount_percent';
    const ATTR_BALANCE_CLOSE = 'balance_close';
    const ATTR_BALANCE_OPEN = 'balance_open';
    const ATTR_CUSTOMER_REF = 'customer_ref';
    const ATTR_DATE_SINCE = 'date_since';
    const ATTR_DATE_TERM = 'date_term';
    const ATTR_MONTHS_INACT = 'months_inact';
    const ATTR_MONTHS_LEFT = 'months_left';
    const ATTR_MONTHS_TOTAL = 'months_total';
    const ENTITY_NAME = 'prxgt_pens_reg';

    /** @return float */
    public function getAmountIn()
    {
        $result = parent::get(self::ATTR_AMOUNT_IN);
        return $result;
    }

    /** @return float */
    public function getAmountPercent()
    {
        $result = parent::get(self::ATTR_AMOUNT_PERCENT);
        return $result;
    }

    /** @return float */
    public function getBalanceClose()
    {
        $result = parent::get(self::ATTR_BALANCE_CLOSE);
        return $result;
    }

    /** @return float */
    public function getBalanceOpen()
    {
        $result = parent::get(self::ATTR_BALANCE_OPEN);
        return $result;
    }

    /** @return int */
    public function getCustomerRef()
    {
        $result = parent::get(self::ATTR_CUSTOMER_REF);
        return $result;
    }

    /** @return string */
    public function getDateSince()
    {
        $result = parent::get(self::ATTR_DATE_SINCE);
        return $result;
    }

    /** @return string */
    public function getDateTerm()
    {
        $result = parent::get(self::ATTR_DATE_TERM);
        return $result;
    }

    /** @return int */
    public function getMonthsInact()
    {
        $result = parent::get(self::ATTR_MONTHS_INACT);
        return $result;
    }

    /** @return int */
    public function getMonthsLeft()
    {
        $result = parent::get(self::ATTR_MONTHS_LEFT);
        return $result;
    }

    /** @return int */
    public function getMonthsTotal()
    {
        $result = parent::get(self::ATTR_MONTHS_TOTAL);
        return $result;
    }

    public static function getPrimaryKeyAttrs()
    {
        return [self::ATTR_CUSTOMER_REF];
    }

    /** @param float $data */
    public function setAmountIn($data)
    {
        parent::set(self::ATTR_AMOUNT_IN, $data);
    }

    /** @param float $data */
    public function setAmountPercent($data)
    {
        parent::set(self::ATTR_AMOUNT_PERCENT, $data);
    }

    /** @param float $data */
    public function setBalanceClose($data)
    {
        parent::set(self::ATTR_BALANCE_CLOSE, $data);
    }

    /** @param float $data */
    public function setBalanceOpen($data)
    {
        parent::set(self::ATTR_BALANCE_OPEN, $data);
    }

    /** @param int $data */
    public function setCustomerRef($data)
    {
        parent::set(self::ATTR_CUSTOMER_REF, $data);
    }

    /** @param string $data */
    public function setDateSince($data)
    {
        parent::set(self::ATTR_DATE_SINCE, $data);
    }

    /** @param float $data */
    public function setDateTerm($data)
    {
        parent::set(self::ATTR_DATE_TERM, $data);
    }

    /** @param int $data */
    public function setMonthsInact($data)
    {
        parent::set(self::ATTR_MONTHS_INACT, $data);
    }

    /** @param int $data */
    public function setMonthsLeft($data)
    {
        parent::set(self::ATTR_MONTHS_LEFT, $data);
    }

    /** @param int $data */
    public function setMonthsTotal($data)
    {
        parent::set(self::ATTR_MONTHS_TOTAL, $data);
    }

}