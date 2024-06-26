<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect;

use Praxigento\BonusBase\Repo\Data\Log\Opers as ELogOper;
use Praxigento\Downline\Repo\Data\Customer as EDwnlCust;
use Praxigento\PensionFund\Config as Cfg;
use Praxigento\PensionFund\Service\Collect\Assets\A\Repo\Query\GetFee as QBGetFee;
use Praxigento\PensionFund\Service\Collect\Assets\Request as ARequest;
use Praxigento\PensionFund\Service\Collect\Assets\Response as AResponse;

/**
 * Module level service to collect pension related data and add assets to pension accounts for
 * the last calculated period.
 */
class Assets {
    /** @var \Praxigento\PensionFund\Service\Collect\Assets\A\GetQualified */
    private $ownGetQual;
    /** @var \Praxigento\PensionFund\Service\Collect\Assets\A\ProcessQualified */
    private $ownProcQual;
    /** @var \Praxigento\PensionFund\Service\Collect\Assets\A\ProcessUnqualified */
    private $ownProcUnqual;
    /** @var \Praxigento\PensionFund\Service\Collect\Assets\A\Repo\Query\GetFee */
    private $qbGetFee;
    /** @var \Praxigento\BonusBase\Repo\Dao\Calculation */
    private $daoCalc;
    /** @var \Praxigento\BonusBase\Repo\Dao\Log\Opers */
    private $daoLogOper;
    /** @var \Praxigento\PensionFund\Repo\Dao\Registry */
    private $daoReg;
    /** @var \Praxigento\Downline\Repo\Dao\Customer */
    private $daoDwnlCust;
    /** @var \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent */
    private $servCalcDep;

    public function __construct(
        \Praxigento\PensionFund\Repo\Dao\Registry $daoReg,
        \Praxigento\BonusBase\Repo\Dao\Calculation $daoCalc,
        \Praxigento\BonusBase\Repo\Dao\Log\Opers $daoLogOper,
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnlCust,
        \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent $servCalcDep,
        \Praxigento\PensionFund\Service\Collect\Assets\A\Repo\Query\GetFee $qbGetFee,
        \Praxigento\PensionFund\Service\Collect\Assets\A\GetQualified $ownGetQual,
        \Praxigento\PensionFund\Service\Collect\Assets\A\ProcessQualified $ownProcQual,
        \Praxigento\PensionFund\Service\Collect\Assets\A\ProcessUnqualified $ownProcUnqual
    )
    {
        $this->daoReg = $daoReg;
        $this->daoCalc = $daoCalc;
        $this->daoLogOper = $daoLogOper;
        $this->daoDwnlCust = $daoDwnlCust;
        $this->servCalcDep = $servCalcDep;
        $this->qbGetFee = $qbGetFee;
        $this->ownGetQual = $ownGetQual;
        $this->ownProcQual = $ownProcQual;
        $this->ownProcUnqual = $ownProcUnqual;
    }

    /**
     * @param ARequest $request
     * @return AResponse
     * @throws \Exception
     */
    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);

        /** perform processing */
        /**
         * @var \Praxigento\BonusBase\Repo\Data\Period $pensPeriod
         * @var \Praxigento\BonusBase\Repo\Data\Calculation $pensCalc
         * @var \Praxigento\BonusBase\Repo\Data\Calculation $cmprsCalc
         * @var \Praxigento\BonusBase\Repo\Data\Calculation $feeCalc
         */
        [$pensPeriod, $pensCalc, $cmprsCalc, $feeCalc] = $this->getCalcData();
        $dsEnd = $pensPeriod->getDstampEnd();
        $cmprsCalcId = $cmprsCalc->getId();
        $feeCalcId = $feeCalc->getId();
        $pensCalcId = $pensCalc->getId();
        $fee = $this->getFee($feeCalcId);
        $period = substr($dsEnd, 0, 6);
        /* get all qualified customers */
        $qual = $this->ownGetQual->exec($cmprsCalcId);
        $registry = $this->getPensionRegistry();
        $warCusts = $this->getWarCustomers();
        $unqual = $this->collectUnqualPensioners($registry, $qual, $warCusts);
        [$operIdIncome, $operIdPercent, $operIdReturn] = $this->ownProcQual->exec($registry, $qual, $unqual, $fee, $period, $warCusts);
        [$operIdCleanup] = $this->ownProcUnqual->exec($registry, $qual, $unqual, $period, $warCusts);
        /* register operation in log then mark calculation as complete */
        if ($operIdIncome) $this->saveLogOper($operIdIncome, $pensCalcId);
        if ($operIdPercent) $this->saveLogOper($operIdPercent, $pensCalcId);
        if ($operIdReturn) $this->saveLogOper($operIdReturn, $pensCalcId);
        if ($operIdCleanup) $this->saveLogOper($operIdCleanup, $pensCalcId);
        $this->daoCalc->markComplete($pensCalcId);
        /** compose result */
        $result = new AResponse();
        $result->setOperIdIncome($operIdIncome);
        $result->setOperIdPercent($operIdPercent);
        $result->setOperIdReturn($operIdReturn);
        $result->setOperIdCleanup($operIdCleanup);
        return $result;
    }

    /**
     * Select IDs of the customers from RU & UA.
     * @return array
     */
    private function getWarCustomers()
    {
        $result = [];
        $whereUa = EDwnlCust::A_COUNTRY_CODE . '="UA"';
        $where = "($whereUa)";
        $all = $this->daoDwnlCust->get($where);
        /** @var EDwnlCust $one */
        foreach ($all as $one) {
            $result[] = $one->getCustomerRef();
        }
        return $result;
    }

    /**
     * Get data for period & related calculations.
     *
     * @return array [$pensPeriod, $pensCalc, $cmprsCalc, $feeCalc]
     * @throws \Exception
     */
    private function getCalcData()
    {
        /* get period & calc data */
        $req = new \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent\Request();
        $req->setBaseCalcTypeCode(Cfg::CODE_TYPE_CALC_PROC_FEE);
        $req->setDepCalcTypeCode(Cfg::CODE_TYPE_CALC_PENSION);
        $resp = $this->servCalcDep->exec($req);
        /** @var \Praxigento\BonusBase\Repo\Data\Period $pensPeriod */
        $pensPeriod = $resp->getDepPeriodData();
        /** @var \Praxigento\BonusBase\Repo\Data\Calculation $pensCalc */
        $pensCalc = $resp->getDepCalcData();
        /* get additional calculation data */
        $periodEnd = $pensPeriod->getDstampEnd();
        $req->setBaseCalcTypeCode(Cfg::CODE_TYPE_CALC_COMPRESS_PHASE1);
        $req->setDepCalcTypeCode(Cfg::CODE_TYPE_CALC_PROC_FEE);
        $req->setDepIgnoreComplete(true);
        $req->setPeriodEnd($periodEnd);
        $resp = $this->servCalcDep->exec($req);
        $cmprsCalc = $resp->getBaseCalcData();
        $feeCalc = $resp->getDepCalcData();
        /* compose result */
        $result = [$pensPeriod, $pensCalc, $cmprsCalc, $feeCalc];
        return $result;
    }

    /**
     * Retrieve processing fee operation related to the given calculation and return summary for all fees.
     *
     * @param $calcId
     * @return float
     */
    private function getFee($calcId)
    {
        $query = $this->qbGetFee->build();
        $conn = $query->getConnection();
        $bind = [
            QBGetFee::BND_OPER_TYPE_CODE => Cfg::CODE_TYPE_OPER_PROC_FEE_DEF,
            QBGetFee::BND_CALC_ID => $calcId
        ];
        $rs = $conn->fetchOne($query, $bind);
        $result = (float)$rs;
        return $result;
    }

    /**
     * @return \Praxigento\PensionFund\Repo\Data\Registry[]
     */
    private function getPensionRegistry()
    {
        $result = [];
        $items = $this->daoReg->get();
        /** @var \Praxigento\PensionFund\Repo\Data\Registry $item */
        foreach ($items as $item) {
            $custId = $item->getCustomerRef();
            $result[$custId] = $item;
        }
        return $result;
    }

    /**
     * Collect unqualified pensioners (first timers).
     *
     * @param \Praxigento\PensionFund\Repo\Data\Registry[] $registry on state for the end of previous period
     * @param int[] $qualified
     * @param int[] $warCusts
     * @return int[]
     */
    private function collectUnqualPensioners($registry, $qualified, $warCusts)
    {
        $result = [];
        foreach ($registry as $one) {
            $custId = $one->getCustomerRef();
            $isQual = in_array($custId, $qualified);
            if (!$isQual) {
                $monthsInact = $one->getMonthsInact();
                if (($monthsInact <= 0)|| in_array($custId, $warCusts)) {
                    /* this customer is unqualified first time in the year or it is the UA customer */
                    $result[] = $custId;
                }
            }
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
