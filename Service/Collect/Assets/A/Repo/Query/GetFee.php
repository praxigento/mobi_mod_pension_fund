<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Assets\A\Repo\Query;

use Praxigento\Accounting\Repo\Data\Operation as EOper;
use Praxigento\Accounting\Repo\Data\Transaction as ETrans;
use Praxigento\Accounting\Repo\Data\Type\Operation as ETypeOper;
use Praxigento\BonusBase\Repo\Data\Log\Opers as ELogOper;
use Praxigento\Core\App\Repo\Query\Expression as AnExpression;

/**
 * Query to get total amount of the processing fee for given calculation.
 *
 * SELECT
 * (
 * SUM( trans.value )
 * ) AS `fee`
 * FROM
 * `prxgt_bon_base_log_opers` AS `log`
 * LEFT JOIN `prxgt_acc_operation` AS `oper` ON
 * oper.id = log.oper_id
 * LEFT JOIN `prxgt_acc_type_operation` AS `operType` ON
 * operType.id = oper.type_id
 * LEFT JOIN `prxgt_acc_transaction` AS `trans` ON
 * trans.operation_id = oper.id
 * WHERE
 * (
 * (
 * operType.code =:operTypeCode
 * )
 * AND(
 * log.calc_id =:calcId
 * )
 * )
 */
class GetFee
    extends \Praxigento\Core\App\Repo\Query\Builder
{

    /** Tables aliases for external usage ('camelCase' naming) */
    const AS_LOG = 'log';
    const AS_OPER = 'oper';
    const AS_OPER_TYPE = 'operType';
    const AS_TRANS = 'trans';

    /** Columns/expressions aliases for external usage ('camelCase' naming) */
    const A_FEE = 'fee';

    /** Bound variables names ('camelCase' naming) */
    const BND_CALC_ID = 'calcId';
    const BND_OPER_TYPE_CODE = 'operTypeCode';

    /** Entities are used in the query */
    const E_LOG_OPER = ELogOper::ENTITY_NAME;
    const E_OPER = EOper::ENTITY_NAME;
    const E_TRANS = ETrans::ENTITY_NAME;
    const E_TYPE_OPER = ETypeOper::ENTITY_NAME;

    public function build(\Magento\Framework\DB\Select $source = null)
    {
        $result = $this->conn->select();

        /* define tables aliases for internal usage (in this method) */
        $asLog = self::AS_LOG;
        $asOper = self::AS_OPER;
        $asTrans = self::AS_TRANS;
        $asType = self::AS_OPER_TYPE;

        /* FROM prxgt_bon_base_log_opers */
        $tbl = $this->resource->getTableName(self::E_LOG_OPER);
        $as = $asLog;
        $cols = [];
        $result->from([$as => $tbl], $cols);

        /* LEFT JOIN prxgt_acc_operation */
        $tbl = $this->resource->getTableName(self::E_OPER);
        $as = $asOper;
        $cols = [];
        $cond = $as . '.' . EOper::A_ID . '=' . $asLog . '.' . ELogOper::A_OPER_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN prxgt_acc_type_operation */
        $tbl = $this->resource->getTableName(self::E_TYPE_OPER);
        $as = $asType;
        $cols = [];
        $cond = $as . '.' . ETypeOper::A_ID . '=' . $asOper . '.' . EOper::A_TYPE_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN prxgt_acc_transaction */
        $tbl = $this->resource->getTableName(self::E_TRANS);
        $as = $asTrans;
        $exp = "SUM($asTrans." . ETrans::A_VALUE . ")";
        $exp = new AnExpression($exp);
        $cols = [self::A_FEE => $exp];
        $cond = $as . '.' . ETrans::A_OPERATION_ID . '=' . $asOper . '.' . EOper::A_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* query tuning */
        $byOperTypeCode = "$asType." . ETypeOper::A_CODE . "=:" . self::BND_OPER_TYPE_CODE;
        $byCalcId = "$asLog." . ELogOper::A_CALC_ID . "=:" . self::BND_CALC_ID;
        $result->where("($byOperTypeCode) AND ($byCalcId)");

        return $result;
    }

}