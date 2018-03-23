<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Repo\Data;

/**
 * Registry for pension fund customers.
 */
class Registry
    extends \Praxigento\Core\App\Repo\Data\Entity\Base
{
    const A_AMOUNT_IN = 'amount_in';
    const A_AMOUNT_PERCENT = 'amount_percent';
    const A_AMOUNT_RETURNED = 'amount_returned';
    const A_BALANCE_CLOSE = 'balance_close';
    const A_BALANCE_OPEN = 'balance_open';
    const A_CUSTOMER_REF = 'customer_ref';
    const A_MONTHS_INACT = 'months_inact';
    const A_MONTHS_LEFT = 'months_left';
    const A_MONTHS_TOTAL = 'months_total';
    const A_PERIOD_SINCE = 'period_since';
    const A_PERIOD_TERM = 'period_term';
    const ENTITY_NAME = 'prxgt_pens_reg';

    /** @return float */
    public function getAmountIn()
    {
        $result = parent::get(self::A_AMOUNT_IN);
        return $result;
    }

    /** @return float */
    public function getAmountPercent()
    {
        $result = parent::get(self::A_AMOUNT_PERCENT);
        return $result;
    }

    /** @return float */
    public function getAmountReturned()
    {
        $result = parent::get(self::A_AMOUNT_RETURNED);
        return $result;
    }

    /** @return float */
    public function getBalanceClose()
    {
        $result = parent::get(self::A_BALANCE_CLOSE);
        return $result;
    }

    /** @return float */
    public function getBalanceOpen()
    {
        $result = parent::get(self::A_BALANCE_OPEN);
        return $result;
    }

    /** @return int */
    public function getCustomerRef()
    {
        $result = parent::get(self::A_CUSTOMER_REF);
        return $result;
    }

    /** @return int */
    public function getMonthsInact()
    {
        $result = parent::get(self::A_MONTHS_INACT);
        return $result;
    }

    /** @return int */
    public function getMonthsLeft()
    {
        $result = parent::get(self::A_MONTHS_LEFT);
        return $result;
    }

    /** @return int */
    public function getMonthsTotal()
    {
        $result = parent::get(self::A_MONTHS_TOTAL);
        return $result;
    }

    /** @return string */
    public function getPeriodSince()
    {
        $result = parent::get(self::A_PERIOD_SINCE);
        return $result;
    }

    /** @return string */
    public function getPeriodTerm()
    {
        $result = parent::get(self::A_PERIOD_TERM);
        return $result;
    }

    public static function getPrimaryKeyAttrs()
    {
        return [self::A_CUSTOMER_REF];
    }

    /** @param float $data */
    public function setAmountIn($data)
    {
        parent::set(self::A_AMOUNT_IN, $data);
    }

    /** @param float $data */
    public function setAmountPercent($data)
    {
        parent::set(self::A_AMOUNT_PERCENT, $data);
    }

    /** @param float $data */
    public function setAmountReturned($data)
    {
        parent::set(self::A_AMOUNT_RETURNED, $data);
    }

    /** @param float $data */
    public function setBalanceClose($data)
    {
        parent::set(self::A_BALANCE_CLOSE, $data);
    }

    /** @param float $data */
    public function setBalanceOpen($data)
    {
        parent::set(self::A_BALANCE_OPEN, $data);
    }

    /** @param int $data */
    public function setCustomerRef($data)
    {
        parent::set(self::A_CUSTOMER_REF, $data);
    }

    /** @param int $data */
    public function setMonthsInact($data)
    {
        parent::set(self::A_MONTHS_INACT, $data);
    }

    /** @param int $data */
    public function setMonthsLeft($data)
    {
        parent::set(self::A_MONTHS_LEFT, $data);
    }

    /** @param int $data */
    public function setMonthsTotal($data)
    {
        parent::set(self::A_MONTHS_TOTAL, $data);
    }

    /** @param string $data */
    public function setPeriodSince($data)
    {
        parent::set(self::A_PERIOD_SINCE, $data);
    }

    /** @param float $data */
    public function setPeriodTerm($data)
    {
        parent::set(self::A_PERIOD_TERM, $data);
    }

}