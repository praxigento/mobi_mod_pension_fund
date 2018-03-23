<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Fee\Own\Repo\Query;

use Praxigento\Accounting\Repo\Data\Account as EAcc;
use Praxigento\Accounting\Repo\Data\Operation as EOper;
use Praxigento\Accounting\Repo\Data\Transaction as ETrans;
use Praxigento\Core\App\Repo\Query\Expression as AnExpression;

class GetCreditTotals
    extends \Praxigento\Core\App\Repo\Query\Builder
{

    /** Tables aliases for external usage ('camelCase' naming) */
    const AS_ACC = 'acc';
    const AS_OPER = 'oper';
    const AS_TRANS = 'trans';

    /** Columns/expressions aliases for external usage ('camelCase' naming) */
    const A_CREDIT = 'credit';
    const A_CUST_ID = 'custId';

    /** Bound variables names ('camelCase' naming) */
    const BND_ASSET_TYPE_ID = 'assetTypeId';
    const BND_DATE_FROM = 'dateFrom';       // inclusive
    const BND_DATE_TO = 'dateTo';           // exclusive

    /** Entities are used in the query */
    const E_ACC = EAcc::ENTITY_NAME;
    const E_OPER = EOper::ENTITY_NAME;
    const E_TRANS = ETrans::ENTITY_NAME;

    public function build(\Magento\Framework\DB\Select $source = null)
    {

        /* this is root query builder (started from SELECT) */
        $result = $this->conn->select();

        /* define tables aliases for internal usage (in this method) */
        $asAcc = self::AS_ACC;
        $asOper = self::AS_OPER;
        $asTrans = self::AS_TRANS;

        /* FROM prxgt_acc_transaction */
        $tbl = $this->resource->getTableName(self::E_TRANS);
        $as = $asTrans;
        $exp = "SUM($asTrans." . ETrans::A_VALUE . ")";
        $exp = new AnExpression($exp);
        $cols = [
            self::A_CREDIT => $exp
        ];
        $result->from([$as => $tbl], $cols);

        /* LEFT JOIN prxgt_acc_account (to get customer ID) */
        $tbl = $this->resource->getTableName(self::E_ACC);
        $as = $asAcc;
        $cols = [
            self::A_CUST_ID => EAcc::A_CUST_ID
        ];
        $cond = $as . '.' . EAcc::A_ID . '=' . $asTrans . '.' . ETrans::A_CREDIT_ACC_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN prxgt_acc_operation (to apply filters by operation type) */
        $tbl = $this->resource->getTableName(self::E_OPER);
        $as = $asOper;
        $cols = [];
        $cond = $as . '.' . EOper::A_ID . '=' . $asTrans . '.' . ETrans::A_OPERATION_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* query tuning */
        $result->group($asTrans . '.' . ETrans::A_CREDIT_ACC_ID);
        $byFrom = "$asTrans." . ETrans::A_DATE_APPLIED . ">=:" . self::BND_DATE_FROM;
        $byTo = "$asTrans." . ETrans::A_DATE_APPLIED . "<:" . self::BND_DATE_TO;
        $byAssetType = "$asAcc." . EAcc::A_ASSET_TYPE_ID . "=:" . self::BND_ASSET_TYPE_ID;
        $result->where("($byFrom) AND ($byTo) AND ($byAssetType)");

        return $result;
    }
}