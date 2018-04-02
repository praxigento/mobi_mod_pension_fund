<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect;

use Praxigento\Accounting\Repo\Data\Operation as EOper;
use Praxigento\BonusBase\Repo\Data\Log\Opers as ELogOper;
use Praxigento\PensionFund\Config as Cfg;
use Praxigento\PensionFund\Service\Collect\A\GetEuCustomers as AGetEuCust;
use Praxigento\PensionFund\Service\Collect\Fee\Own\Calc as ACalc;
use Praxigento\PensionFund\Service\Collect\Fee\Own\CreateOperation as ACreateOper;
use Praxigento\PensionFund\Service\Collect\Fee\Own\Repo\Query\GetCreditTotals as QBGetCreditTotals;
use Praxigento\PensionFund\Service\Collect\Fee\Request as ARequest;
use Praxigento\PensionFund\Service\Collect\Fee\Response as AResponse;

class Fee
{
    /** @var \Praxigento\PensionFund\Service\Collect\A\GetEuCustomers */
    private $fnGetEuCust;
    /** @var \Praxigento\Core\Api\Helper\Period */
    private $hlpPeriod;
    /** @var \Praxigento\PensionFund\Service\Collect\Fee\Own\Calc */
    private $ownCalc;
    /** @var \Praxigento\PensionFund\Service\Collect\Fee\Own\CreateOperation */
    private $ownCreateOper;
    /** @var \Praxigento\PensionFund\Service\Collect\Fee\Own\Repo\Query\GetCreditTotals */
    private $qbGetCreditTotals;
    /** @var \Praxigento\BonusHybrid\Repo\Dao\Downline */
    private $daoBonDwnl;
    /** @var \Praxigento\BonusBase\Repo\Dao\Calculation */
    private $daoCalc;
    /** @var \Praxigento\BonusBase\Repo\Dao\Log\Opers */
    private $daoLogOper;
    /** @var \Praxigento\Accounting\Repo\Dao\Type\Asset */
    private $daoTypeAsset;
    /** @var \Praxigento\Accounting\Repo\Dao\Type\Operation */
    private $daoTypeOper;
    /** @var \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent */
    private $servCalcDep;

    public function __construct(
        \Praxigento\Accounting\Repo\Dao\Type\Asset $daoTypeAsset,
        \Praxigento\Accounting\Repo\Dao\Type\Operation $daoTypeOper,
        \Praxigento\BonusBase\Repo\Dao\Calculation $daoCalc,
        \Praxigento\BonusBase\Repo\Dao\Log\Opers $daoLogOper,
        \Praxigento\BonusHybrid\Repo\Dao\Downline $daoBonDwnl,
        \Praxigento\Core\Api\Helper\Period $hlpPeriod,
        \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent $servCalcDep,
        AGetEuCust $fnGetEuCust,
        QBGetCreditTotals $qbGetCreditTotals,
        ACalc $ownCalc,
        ACreateOper $ownCreateOper
    ) {
        $this->daoTypeAsset = $daoTypeAsset;
        $this->daoTypeOper = $daoTypeOper;
        $this->daoCalc = $daoCalc;
        $this->daoLogOper = $daoLogOper;
        $this->daoBonDwnl = $daoBonDwnl;
        $this->hlpPeriod = $hlpPeriod;
        $this->servCalcDep = $servCalcDep;
        $this->fnGetEuCust = $fnGetEuCust;
        $this->qbGetCreditTotals = $qbGetCreditTotals;
        $this->ownCalc = $ownCalc;
        $this->ownCreateOper = $ownCreateOper;
    }

    /**
     * @param ARequest $request
     * @return AResponse
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);

        /** perform processing */
        /**
         * @var \Praxigento\BonusBase\Repo\Data\Period $feePeriod
         * @var \Praxigento\BonusBase\Repo\Data\Calculation $feeCalc
         * @var \Praxigento\BonusBase\Repo\Data\Calculation $cmprsCalc
         */
        list($feePeriod, $feeCalc, $cmprsCalc) = $this->getCalcData();
        $dsBegin = $feePeriod->getDstampBegin();
        $dsEnd = $feePeriod->getDstampEnd();
        $feeCalcId = $feeCalc->getId();
        $cmprsCalcId = $cmprsCalc->getId();
        /* get total credit and customers ranks */
        $totals = $this->getCreditTotal($dsBegin, $dsEnd);
        $ranks = $this->getRanks($cmprsCalcId);
        $euCusts = $this->fnGetEuCust->exec();
        list($feeDef, $feeEu) = $this->ownCalc->exec($totals, $ranks, $euCusts);
        $period = substr($dsEnd, 0, 6);
        $operIdDef = $this->ownCreateOper->exec($feeDef, $period, Cfg::CODE_TYPE_OPER_PROC_FEE_DEF);
        $operIdEu = $this->ownCreateOper->exec($feeEu, $period, Cfg::CODE_TYPE_OPER_PROC_FEE_EU);
        /* register operation in log then mark calculation as complete */
        $this->saveLogOper($operIdDef, $feeCalcId);
        $this->saveLogOper($operIdEu, $feeCalcId);
        $this->daoCalc->markComplete($feeCalcId);
        /** compose result */
        $result = new AResponse();
        $result->setOperationId($operIdDef);
        return $result;
    }

    /**
     * Get data for period & calculations.
     *
     * @return array [$periodData, $calcData]
     * @throws \Exception
     */
    private function getCalcData()
    {
        /* get period & calc data */
        $req = new \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent\Request();
        $req->setBaseCalcTypeCode(Cfg::CODE_TYPE_CALC_BONUS_INFINITY_EU);
        $req->setDepCalcTypeCode(Cfg::CODE_TYPE_CALC_PROC_FEE);
        $resp = $this->servCalcDep->exec($req);
        /** @var \Praxigento\BonusBase\Repo\Data\Period $periodData */
        $periodData = $resp->getDepPeriodData();
        /** @var \Praxigento\BonusBase\Repo\Data\Calculation $calcData */
        $calcData = $resp->getDepCalcData();
        /* get additional calculation data */
        $periodEnd = $periodData->getDstampEnd();
        $req->setBaseCalcTypeCode(Cfg::CODE_TYPE_CALC_COMPRESS_PHASE1);
        $req->setDepCalcTypeCode(Cfg::CODE_TYPE_CALC_PROC_FEE);
        $req->setDepIgnoreComplete(true);
        $req->setPeriodEnd($periodEnd);
        $resp = $this->servCalcDep->exec($req);
        $calcCompressData = $resp->getBaseCalcData();
        /* compose result */
        $result = [$periodData, $calcData, $calcCompressData];
        return $result;
    }

    /**
     * Get total bonus amount by customer.
     *
     * @param $dsFrom
     * @param $dsTo
     * @return array [$custId=>$amount]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCreditTotal($dsFrom, $dsTo)
    {
        $dateFrom = $this->hlpPeriod->getTimestampFrom($dsFrom);
        $dateTo = $this->hlpPeriod->getTimestampNextFrom($dsTo);
        $assetWallet = $this->daoTypeAsset->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET);
        $operTypeIds = $this->getOperTypesApplied();

        /* compose query */
        $query = $this->qbGetCreditTotals->build();
        $byOperType = '(0)';
        foreach ($operTypeIds as $typeId) {
            $cond = QBGetCreditTotals::AS_OPER . '.' . EOper::A_TYPE_ID . '=' . (int)$typeId;
            $byOperType .= " OR ($cond)";
        }
        $query->where($byOperType);
        $conn = $query->getConnection();
        $bind = [
            QBGetCreditTotals::BND_DATE_FROM => $dateFrom,
            QBGetCreditTotals::BND_DATE_TO => $dateTo,
            QBGetCreditTotals::BND_ASSET_TYPE_ID => $assetWallet
        ];
        $rs = $conn->fetchAll($query, $bind);
        $result = [];
        foreach ($rs as $one) {
            $custId = $one[QBGetCreditTotals::A_CUST_ID];
            $total = $one[QBGetCreditTotals::A_CREDIT];
            if (isset($result[$custId])) {
                throw  new \Magento\Framework\Exception\LocalizedException("Processing fee: two credits for one customer");
            } else {
                $result[$custId] = $total;
            }
        }
        return $result;
    }

    /**
     * Get IDs for operation types allowed for fee processing.
     *
     * @return int[]
     */
    private function getOperTypesApplied()
    {
        $result = [];
        $result[] = $this->daoTypeOper->getIdByCode(Cfg::CODE_TYPE_OPER_BONUS_AGGREGATE);
        return $result;
    }

    /**
     * Get customer ranks for the period.
     *
     * @param int $cmprsCalcId
     * @return array [$custId=>$rankId]
     */
    private function getRanks($cmprsCalcId)
    {
        $result = [];
        $tree = $this->daoBonDwnl->getByCalcId($cmprsCalcId);
        foreach ($tree as $one) {
            $custId = $one->getCustomerRef();
            $rankId = $one->getRankRef();
            $result[$custId] = $rankId;
        }
        return $result;
    }

    private function getScheme()
    {
        $result = [];
        $tree = $this->daoBonDwnl->getByCalcId($cmprsCalcId);
        foreach ($tree as $one) {
            $custId = $one->getCustomerRef();
            $rankId = $one->getRankRef();
            $result[$custId] = $rankId;
        }
        return $result;
    }

    /**
     * Bind operation with calculation.
     *
     * @param int $operId
     * @param int $calcId
     * @throws \Exception
     */
    private function saveLogOper($operId, $calcId)
    {
        $entity = new ELogOper();
        $entity->setOperId($operId);
        $entity->setCalcId($calcId);
        $this->daoLogOper->create($entity);
    }
}