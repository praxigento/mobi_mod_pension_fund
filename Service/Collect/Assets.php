<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect;

use Praxigento\PensionFund\Config as Cfg;
use Praxigento\PensionFund\Service\Collect\Assets\Own\Repo\Query\GetFee as QBGetFee;
use Praxigento\PensionFund\Service\Collect\Assets\Request as ARequest;
use Praxigento\PensionFund\Service\Collect\Assets\Response as AResponse;

/**
 * Module level service to collect pension related data and add assets to pension accounts for
 * the last calculated period.
 */
class Assets
{
    /** @var \Praxigento\PensionFund\Service\Collect\Assets\Own\GetQualified */
    private $ownGetQual;
    /** @var \Praxigento\PensionFund\Service\Collect\Assets\Own\Repo\Query\GetFee */
    private $qbGetFee;
    /** @var \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent */
    private $servCalcDep;

    public function __construct(
        \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent $servCalcDep,
        \Praxigento\PensionFund\Service\Collect\Assets\Own\Repo\Query\GetFee $qbGetFee,
        \Praxigento\PensionFund\Service\Collect\Assets\Own\GetQualified $ownGetQual
    ) {
        $this->servCalcDep = $servCalcDep;
        $this->qbGetFee = $qbGetFee;
        $this->ownGetQual = $ownGetQual;
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
         * @var \Praxigento\BonusBase\Repo\Entity\Data\Period $pensPeriod
         * @var \Praxigento\BonusBase\Repo\Entity\Data\Calculation $pensCalc
         * @var \Praxigento\BonusBase\Repo\Entity\Data\Calculation $cmprsCalc
         * @var \Praxigento\BonusBase\Repo\Entity\Data\Calculation $feeCalc
         */
        list($pensPeriod, $pensCalc, $cmprsCalc, $feeCalc) = $this->getCalcData();
        $cmprsCalcId = $cmprsCalc->getId();
        $feeCalcId = $feeCalc->getId();
        $ranks = $this->ownGetQual->exec($cmprsCalcId);
        $fee = $this->getFee($feeCalcId);
        /** compose result */
        $result = new AResponse();
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
        /** @var \Praxigento\BonusBase\Repo\Entity\Data\Period $pensPeriod */
        $pensPeriod = $resp->getDepPeriodData();
        /** @var \Praxigento\BonusBase\Repo\Entity\Data\Calculation $pensCalc */
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

    private function getFee($calcId)
    {
        $query = $this->qbGetFee->build();
        $conn = $query->getConnection();
        $bind = [
            QBGetFee::BND_CALC_ID => $calcId
        ];
        $rs = $conn->fetchOne($query, $bind);
        $result = (float)$rs;
        return $result;
    }
}