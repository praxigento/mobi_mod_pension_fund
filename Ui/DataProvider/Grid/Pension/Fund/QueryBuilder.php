<?php

/**
 * File creator: makhovdmitrii@inbox.ru
 */

namespace Praxigento\PensionFund\Ui\DataProvider\Grid\Pension\Fund;

use Praxigento\Downline\Repo\Data\Customer as ECustomer;
use Praxigento\PensionFund\Config as Cfg;
use Praxigento\PensionFund\Repo\Data\Registry as ERegistry;

class QueryBuilder
    extends \Praxigento\Core\App\Ui\DataProvider\Grid\Query\Builder
{
    /**#@+ Tables aliases for external usage ('camelCase' naming) */
    const AS_CUST = 'ce';
    const AS_DWNL_CUSTOMER = 'de';
    const AS_PENS_REG = 'pr';
    /**#@- */

    /**#@+
     * Aliases for data attributes.
     */
    const A_AMOUNT_IN = 'amountIn';
    const A_AMOUNT_PERCENT = 'amountPercent';
    const A_AMOUNT_RETURNED = 'amountReturned';
    const A_BALANCE_CLOSE = 'balanceClose';
    const A_BALANCE_OPEN = 'balanceOpen';
    const A_CUST_ID = 'custId';
    const A_MLM_ID = 'mlmId';
    const A_MONTHS_INACT = 'monthsInact';
    const A_MONTHS_LEFT = 'monthsLeft';
    const A_MONTHS_TOTAL = 'monthsTotal';
    const A_NAME = 'name';
    const A_PERIOD_SINCE = 'periodSince';
    const A_PERIOD_TERM = 'periodTerm';
    /**#@- */


    private function getExpForCustName()
    {
        $value = 'CONCAT(' . self::AS_CUST . '.' . Cfg::E_CUSTOMER_A_FIRSTNAME . ", ' ', " .
            self::AS_CUST . '.' . Cfg::E_CUSTOMER_A_LASTNAME . ')';
        $result = new \Praxigento\Core\App\Repo\Query\Expression($value);
        return $result;
    }

    protected function getMapper()
    {
        $exp = $this->getExpForCustName();
        if (is_null($this->mapper)) {
            $map = [
                self::A_AMOUNT_IN => self::AS_PENS_REG . '.' . ERegistry::A_AMOUNT_IN,
                self::A_AMOUNT_PERCENT => self::AS_PENS_REG . '.' . ERegistry::A_AMOUNT_PERCENT,
                self::A_AMOUNT_RETURNED => self::AS_PENS_REG . '.' . ERegistry::A_AMOUNT_RETURNED,
                self::A_BALANCE_CLOSE => self::AS_PENS_REG . '.' . ERegistry::A_BALANCE_CLOSE,
                self::A_BALANCE_OPEN => self::AS_PENS_REG . '.' . ERegistry::A_BALANCE_OPEN,
                self::A_CUST_ID => self::AS_CUST . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID,
                self::A_MLM_ID => self::AS_DWNL_CUSTOMER . '.' . ECustomer::A_MLM_ID,
                self::A_MONTHS_INACT => self::AS_PENS_REG . '.' . ERegistry::A_MONTHS_INACT,
                self::A_MONTHS_LEFT => self::AS_PENS_REG . '.' . ERegistry::A_MONTHS_LEFT,
                self::A_MONTHS_TOTAL => self::AS_PENS_REG . '.' . ERegistry::A_MONTHS_TOTAL,
                self::A_NAME => $exp,
                self::A_PERIOD_SINCE => self::AS_PENS_REG . '.' . ERegistry::A_PERIOD_SINCE,
                self::A_PERIOD_TERM => self::AS_PENS_REG . '.' . ERegistry::A_PERIOD_TERM
            ];
            $this->mapper = new \Praxigento\Core\App\Repo\Query\Criteria\Def\Mapper($map);
        }
        $result = $this->mapper;
        return $result;
    }

    protected function getQueryItems()
    {
        $result = $this->conn->select();
        /* define tables aliases for internal usage (in this method) */
        $asPensReg = self::AS_PENS_REG;
        $asDwnlCust = self::AS_DWNL_CUSTOMER;
        $asCust = self::AS_CUST;

        /* SELECT FROM prxgt_pens_reg */
        $tbl = $this->resource->getTableName(ERegistry::ENTITY_NAME);
        $as = $asPensReg;
        $cols = [
            self::A_PERIOD_SINCE => ERegistry::A_PERIOD_SINCE,
            self::A_PERIOD_TERM => ERegistry::A_PERIOD_TERM,
            self::A_MONTHS_TOTAL => ERegistry::A_MONTHS_TOTAL,
            self::A_MONTHS_INACT => ERegistry::A_MONTHS_INACT,
            self::A_MONTHS_LEFT => ERegistry::A_MONTHS_LEFT,
            self::A_BALANCE_OPEN => ERegistry::A_BALANCE_OPEN,
            self::A_AMOUNT_IN => ERegistry::A_AMOUNT_IN,
            self::A_AMOUNT_PERCENT => ERegistry::A_AMOUNT_PERCENT,
            self::A_AMOUNT_RETURNED => ERegistry::A_AMOUNT_RETURNED,
            self::A_BALANCE_CLOSE => ERegistry::A_BALANCE_CLOSE
        ];
        $result->from([$as => $tbl], $cols);

        /* LEFT JOIN prxgt_dwnl_customer  */
        $tbl = $this->resource->getTableName(ECustomer::ENTITY_NAME);
        $as = $asDwnlCust;
        $cols = [
            self::A_MLM_ID => ECustomer::A_MLM_ID
        ];
        $cond = $as . '.' . ECustomer::A_CUSTOMER_REF . '=' . $asPensReg . '.' . ERegistry::A_CUSTOMER_REF;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN customer_entity*/
        $tbl = $this->resource->getTableName(Cfg::ENTITY_MAGE_CUSTOMER);
        $as = $asCust;
        $exp = $this->getExpForCustName();
        $cols = [
            self::A_CUST_ID => Cfg::E_CUSTOMER_A_ENTITY_ID,
            self::A_NAME => $exp,
        ];
        $cond = $as . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID . '=' . $asDwnlCust . '.' . ECustomer::A_CUSTOMER_REF;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        return $result;
    }

    protected function getQueryTotal()
    {
        /* get query to select items */
        /** @var \Magento\Framework\DB\Select $result */
        $result = $this->getQueryItems();
        /* ... then replace "columns" part with own expression */
        $value = 'COUNT(' . self::AS_PENS_REG . '.' . ERegistry::A_CUSTOMER_REF . ')';

        /**
         * See method \Magento\Framework\DB\Select\ColumnsRenderer::render:
         */
        /**
         * if ($column instanceof \Zend_Db_Expr) {...}
         */
        $exp = new \Praxigento\Core\App\Repo\Query\Expression($value);
        /**
         *  list($correlationName, $column, $alias) = $columnEntry;
         */
        $entry = [null, $exp, null];
        $cols = [$entry];
        $result->setPart('columns', $cols);
        return $result;
    }
}
