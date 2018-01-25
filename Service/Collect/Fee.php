<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect;

use Praxigento\Accounting\Repo\Entity\Data\Operation as EOper;
use Praxigento\PensionFund\Config as Cfg;
use Praxigento\PensionFund\Service\Collect\Fee\Own\Repo\Query\GetCreditTotals as QBGetCreditTotals;
use Praxigento\PensionFund\Service\Collect\Fee\Request as ARequest;
use Praxigento\PensionFund\Service\Collect\Fee\Response as AResponse;

class Fee
{
    /** @var \Praxigento\Core\Api\Helper\Period */
    private $hlpPeriod;
    /** @var \Praxigento\PensionFund\Service\Collect\Fee\Own\Repo\Query\GetCreditTotals */
    private $qbGetCreditTotals;
    /** @var \Praxigento\Accounting\Repo\Entity\Type\Asset */
    private $repoTypeAsset;
    /** @var \Praxigento\Accounting\Repo\Entity\Type\Operation */
    private $repoTypeOper;
    /** @var \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent */
    private $servCalcDep;

    public function __construct(
        \Praxigento\Accounting\Repo\Entity\Type\Asset $repoTypeAsset,
        \Praxigento\Accounting\Repo\Entity\Type\Operation $repoTypeOper,
        \Praxigento\Core\Api\Helper\Period $hlpPeriod,
        \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent $servCalcDep,
        QBGetCreditTotals $qbGetCreditTotals
    ) {
        $this->repoTypeAsset = $repoTypeAsset;
        $this->repoTypeOper = $repoTypeOper;
        $this->hlpPeriod = $hlpPeriod;
        $this->servCalcDep = $servCalcDep;
        $this->qbGetCreditTotals = $qbGetCreditTotals;
    }


    /**
     * @param ARequest $request
     * @return AResponse
     */
    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);

        /** perform processing */
        /**
         * @var \Praxigento\BonusBase\Repo\Entity\Data\Period $feePeriodData
         * @var \Praxigento\BonusBase\Repo\Entity\Data\Calculation $feeCalcData
         */
        list($feePeriodData, $feeCalcData) = $this->getCalcData();
        $dsBegin = $feePeriodData->getDstampBegin();
        $dsEnd = $feePeriodData->getDstampEnd();

        $bu = $this->getCreditTotal($dsBegin, $dsEnd);
        /** compose result */
        $result = new AResponse();
        return $result;
    }

    /**
     * Get data for dependent calculation.
     *
     * @return array [$periodData, $calcData]
     */
    private function getCalcData()
    {
        /* get period & calc data */
        $req = new \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent\Request();
        $req->setBaseCalcTypeCode(Cfg::CODE_TYPE_CALC_BONUS_INFINITY_EU);
        $req->setDepCalcTypeCode(Cfg::CODE_TYPE_CALC_PROC_FEE);
        $resp = $this->servCalcDep->exec($req);
        /** @var \Praxigento\BonusBase\Repo\Entity\Data\Period $periodData */
        $periodData = $resp->getDepPeriodData();
        /** @var \Praxigento\BonusBase\Repo\Entity\Data\Calculation $calcData */
        $calcData = $resp->getDepCalcData();
        $result = [$periodData, $calcData];
        return $result;
    }

    private function getCreditTotal($dsFrom, $dsTo)
    {
        $dateFrom = $this->hlpPeriod->getTimestampFrom($dsFrom);
        $dateTo = $this->hlpPeriod->getTimestampNextFrom($dsTo);
        $assetWallet = $this->repoTypeAsset->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET);
        $operTypeIds = $this->getOperTypesApplied();

        /* compose query */
        $query = $this->qbGetCreditTotals->build();
        $byOperType = '(0)';
        foreach ($operTypeIds as $typeId) {
            $cond = QBGetCreditTotals::AS_OPER . '.' . EOper::ATTR_TYPE_ID . '=' . (int)$typeId;
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
    }

    /**
     * Get IDs for operation types allowed for fee processing.
     *
     * @return int[]
     */
    private function getOperTypesApplied()
    {
        $result = [];
        $result[] = $this->repoTypeOper->getIdByCode(Cfg::CODE_TYPE_OPER_BONUS_COURTESY);
        $result[] = $this->repoTypeOper->getIdByCode(Cfg::CODE_TYPE_OPER_BONUS_INFINITY);
        $result[] = $this->repoTypeOper->getIdByCode(Cfg::CODE_TYPE_OPER_BONUS_OVERRIDE);
        $result[] = $this->repoTypeOper->getIdByCode(Cfg::CODE_TYPE_OPER_BONUS_PERSONAL);
        $result[] = $this->repoTypeOper->getIdByCode(Cfg::CODE_TYPE_OPER_BONUS_REBATE);
        $result[] = $this->repoTypeOper->getIdByCode(Cfg::CODE_TYPE_OPER_BONUS_SIGNUP_DEBIT);
        $result[] = $this->repoTypeOper->getIdByCode(Cfg::CODE_TYPE_OPER_BONUS_TEAM);
        return $result;
    }
}