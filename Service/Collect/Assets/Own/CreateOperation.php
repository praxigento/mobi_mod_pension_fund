<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Assets\Own;

use Praxigento\Accounting\Api\Service\Operation\Request as AReqOper;
use Praxigento\Accounting\Api\Service\Operation\Response as ARespOper;
use Praxigento\Accounting\Repo\Entity\Data\Transaction as ETrans;
use Praxigento\PensionFund\Config as Cfg;
use Praxigento\PensionFund\Repo\Entity\Data\Registry as EPensReg;

/**
 * Create operation with transactions for pension incomes for the period.
 */
class CreateOperation
{
    /** @var \Praxigento\Core\Api\Helper\Period */
    private $hlpPeriod;
    /** @var \Praxigento\Accounting\Repo\Entity\Account */
    private $repoAcc;
    /** @var \Praxigento\Accounting\Repo\Entity\Type\Asset */
    private $repoAssetType;
    /** @var \Praxigento\Accounting\Api\Service\Operation */
    private $servOper;

    public function __construct(
        \Praxigento\Accounting\Repo\Entity\Account $repoAcc,
        \Praxigento\Accounting\Repo\Entity\Type\Asset $repoAssetType,
        \Praxigento\Core\Api\Helper\Period $hlpPeriod,
        \Praxigento\Accounting\Api\Service\Operation $servOper
    ) {
        $this->repoAcc = $repoAcc;
        $this->repoAssetType = $repoAssetType;
        $this->hlpPeriod = $hlpPeriod;
        $this->servOper = $servOper;
    }

    /**
     * @param \Praxigento\PensionFund\Repo\Entity\Data\Registry[] $registry
     * @param int[] $qualified array with IDs of the qualified customers
     * @param float $fee total amount of the processing fee for period
     * @param string $period 'YYYYMM'
     * @return int id of the created operation
     * @throws \Exception
     */
    public function exec($registry, $qualified, $fee, $period)
    {
        /** define local working data */
        $assetTypeId = $this->repoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_PENSION);
        $accIdRepres = $this->repoAcc->getRepresentativeAccountId($assetTypeId);
        $ds = $this->hlpPeriod->getPeriodLastDate($period);
        $dateApplied = $this->hlpPeriod->getTimestampUpTo($ds);
        $totalCustomers = count($qualified);
        $income = floor($fee / $totalCustomers * 100) / 100;
        /** perform processing */
        /* prepare registry updates & pension income transactions */
        $updates = [];
        $trans = [];
        foreach ($qualified as $custId) {
            $update = $this->prepareRegUpdate($custId, $income, $registry, $period);
            $updates[] = $update;
            $accCust = $this->repoAcc->getByCustomerId($custId, $assetTypeId);
            $accIdCust = $accCust->getId();
            $tran = new ETrans();
            $tran->setDebitAccId($accIdCust);
            $tran->setCreditAccId($accIdRepres);
            $tran->setValue($income);
            $tran->setDateApplied($dateApplied);
            $note = "Pension income for period #$period.";
            $operType = Cfg::CODE_TYPE_OPER_PENSION;
            $trans[] = $tran;
        }
        /* create operation */
        $req = new AReqOper();
        $req->setOperationTypeCode($operType);
        $req->setTransactions($trans);
        $req->setOperationNote($note);
        /** @var ARespOper $resp */
        $resp = $this->servOper->exec($req);

        /** compose result */
        $result = $resp->getOperationId();
        return $result;
    }

    private function prepareRegUpdate($custId, $income, $registry, $period)
    {
        if (isset($registry[$custId])) {
            $result = $registry[$custId];
        } else {
            $result = new EPensReg();
            $result->setPeriodSince($period);
        }
        /* open balance equals to the close balance for the previous period */
        $balanceOpen = (float)$result->getBalanceClose();
        $total = $balanceOpen + $income;
        /* 3% per year = 0.03 / 12 per month */
        $percent = floor($total * Cfg::DEF_PENSION_INTEREST_PERCENT / 12 * 100) / 100;
        $balanceClose = $balanceOpen + $income + $percent;
        /** compose result */
        $result->setBalanceOpen($balanceOpen);
        $result->setAmountIn($income);
        $result->setAmountPercent($percent);
        $result->setBalanceClose($balanceClose);
        return $result;
    }
}