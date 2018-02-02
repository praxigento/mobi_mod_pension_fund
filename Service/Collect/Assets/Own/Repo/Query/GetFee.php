<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Assets\Own\Repo\Query;

use Praxigento\Accounting\Repo\Entity\Data\Transaction as ETrans;
use Praxigento\BonusBase\Repo\Entity\Data\Log\Opers as ELogOpers;
use Praxigento\Core\App\Repo\Query\Expression as AnExpression;

/**
 * Query to get total amount of the processing fee for given calculation.
 */
class GetFee
    extends \Praxigento\Core\App\Repo\Query\Builder
{

    /** Tables aliases for external usage ('camelCase' naming) */
    const AS_LOG = 'log';
    const AS_TRANS = 'trans';

    /** Columns/expressions aliases for external usage ('camelCase' naming) */
    const A_FEE = 'fee';

    /** Bound variables names ('camelCase' naming) */
    const BND_CALC_ID = 'calcId';


    /** Entities are used in the query */
    const E_LOG_OPERS = ELogOpers::ENTITY_NAME;
    const E_TRANS = ETrans::ENTITY_NAME;

    public function build(\Magento\Framework\DB\Select $source = null)
    {
        $result = $this->conn->select();

        /* define tables aliases for internal usage (in this method) */
        $asLog = self::AS_LOG;
        $asTrans = self::AS_TRANS;

        /* FROM prxgt_bon_base_log_opers */
        $tbl = $this->resource->getTableName(self::E_LOG_OPERS);
        $as = $asLog;
        $cols = [];
        $result->from([$as => $tbl], $cols);

        /* LEFT JOIN prxgt_acc_transaction */
        $tbl = $this->resource->getTableName(self::E_TRANS);
        $as = $asTrans;
        $exp = "SUM($asTrans." . ETrans::ATTR_VALUE . ")";
        $exp = new AnExpression($exp);
        $cols = [self::A_FEE => $exp];
        $cond = $as . '.' . ETrans::ATTR_OPERATION_ID . '=' . $asLog . '.' . ELogOpers::ATTR_OPER_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* query tuning */
        $byCalcId = "$asLog." . ELogOpers::ATTR_CALC_ID . "=:" . self::BND_CALC_ID;
        $result->where($byCalcId);

        return $result;
    }

}