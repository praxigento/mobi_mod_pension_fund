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
 * Update registry data and create operations for pension incomes & percents for the period.
 */
class ProcessQualified
{
    /** @var \Praxigento\Core\Api\Helper\Period */
    private $hlpPeriod;
    /** @var \Praxigento\Accounting\Repo\Entity\Account */
    private $repoAcc;
    /** @var \Praxigento\Accounting\Repo\Entity\Type\Asset */
    private $repoAssetType;
    /** @var \Praxigento\PensionFund\Repo\Entity\Registry */
    private $repoReg;
    /** @var \Praxigento\Accounting\Api\Service\Operation */
    private $servOper;

    public function __construct(
        \Praxigento\Accounting\Repo\Entity\Account $repoAcc,
        \Praxigento\PensionFund\Repo\Entity\Registry $repoReg,
        \Praxigento\Accounting\Repo\Entity\Type\Asset $repoAssetType,
        \Praxigento\Core\Api\Helper\Period $hlpPeriod,
        \Praxigento\Accounting\Api\Service\Operation $servOper
    ) {
        $this->repoAcc = $repoAcc;
        $this->repoReg = $repoReg;
        $this->repoAssetType = $repoAssetType;
        $this->hlpPeriod = $hlpPeriod;
        $this->servOper = $servOper;
    }

    /**
     * @param string $operTypeCode
     * @param ETrans[] $trans
     * @param string $note
     * @return int
     * @throws \Exception
     */
    private function createOperation($operTypeCode, $trans, $note)
    {
        $req = new AReqOper();
        $req->setOperationTypeCode($operTypeCode);
        $req->setTransactions($trans);
        $req->setOperationNote($note);
        /** @var ARespOper $resp */
        $resp = $this->servOper->exec($req);
        $result = $resp->getOperationId();
        return $result;
    }

    /**
     * @param \Praxigento\PensionFund\Repo\Entity\Data\Registry[] $registry
     * @param int[] $qualified array with IDs of the qualified customers
     * @param float $fee total amount of the processing fee for period
     * @param string $period 'YYYYMM'
     * @return int[] IDs of the created operations ([$operIdPens, $operIdPercent])
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
        $notePens = "Pension income for period #$period.";
        $notePercent = "Pension percents for period #$period.";
        /** perform processing */
        /* prepare registry updates & pension income transactions */
        $updates = [];
        $transPens = [];
        $transPercent = [];
        foreach ($qualified as $custId) {
            $update = $this->prepareRegUpdate($custId, $income, $registry, $period);
            $updates[] = $update;
            $accCust = $this->repoAcc->getByCustomerId($custId, $assetTypeId);
            $accIdCust = $accCust->getId();
            /* create income transaction */
            $tranPens = new ETrans();
            $tranPens->setDebitAccId($accIdCust);
            $tranPens->setCreditAccId($accIdRepres);
            $tranPens->setValue($update->getAmountIn());
            $tranPens->setDateApplied($dateApplied);
            $tranPens->setNote($notePens);
            $transPens[] = $tranPens;
            /* create percent transaction */
            $tranPercent = new ETrans();
            $tranPercent->setDebitAccId($accIdCust);
            $tranPercent->setCreditAccId($accIdRepres);
            $tranPercent->setValue($update->getAmountIn());
            $tranPercent->setDateApplied($dateApplied);
            $tranPercent->setNote($notePercent);
            $transPercent[] = $tranPercent;
        }
        /* create operations */
        $operIdPens = $this->createOperation(Cfg::CODE_TYPE_OPER_PENSION, $transPens, $notePens);
        $operIdPercent = $this->createOperation(Cfg::CODE_TYPE_OPER_PENSION_PERCENT, $transPercent, $notePercent);
        /* save pension registry updates */
        $this->updateRegistry($updates);

        /** compose result */
        return [$operIdPens, $operIdPercent];
    }

    /**
     * Prepare data to update pension registry.
     *
     * @param $custId
     * @param $income
     * @param $registry
     * @param $period
     * @return \Praxigento\PensionFund\Repo\Entity\Data\Registry
     * @throws \Exception
     */
    private function prepareRegUpdate($custId, $income, $registry, $period)
    {
        if (isset($registry[$custId])) {
            $result = $registry[$custId];
        } else {
            /* initial data for pension registry */
            $result = new EPensReg();
            $result->setAmountIn(0);
            $result->setAmountPercent(0);
            $result->setAmountReturned(0);
            $result->setBalanceClose(0);
            $result->setBalanceOpen(0);
            $result->setCustomerRef($custId);
            $result->setMonthsInact(0);
            $result->setMonthsLeft(12);
            $result->setMonthsTotal(0);
            $result->setPeriodSince($period);
            $result->setPeriodTerm(null);
        }
        /* open balance equals to the close balance for the previous period */
        $balanceOpen = (float)$result->getBalanceClose();
        /* 3% per year = 0.03 / 12 per month */
        $percent = floor($balanceOpen * Cfg::DEF_PENSION_INTEREST_PERCENT / 12 * 100) / 100;
        $balanceClose = $balanceOpen + $income + $percent;
        $monthsInact = $result->getMonthsInact();
        $monthsLeft = $result->getMonthsLeft();
        $monthsTotal = $result->getMonthsTotal();
        /* this customer is active in the current period, switch months */
        if ($monthsLeft <= 1) {
            /* start next year */
            $monthsLeft = 12;
            $monthsInact = 0;
        }
        $monthsLeft--;
        $monthsTotal++;
        /** compose result */
        $result->setBalanceOpen($balanceOpen);
        $result->setAmountIn($income);
        $result->setAmountPercent($percent);
        $result->setBalanceClose($balanceClose);
        $result->setMonthsInact($monthsInact);
        $result->setMonthsLeft($monthsLeft);
        $result->setMonthsTotal($monthsTotal);
        return $result;
    }

    /**
     * @param \Praxigento\PensionFund\Repo\Entity\Data\Registry[] $updates
     */
    private function updateRegistry($updates)
    {
        /** @var \Praxigento\PensionFund\Repo\Entity\Data\Registry $one */
        foreach ($updates as $one) {
            $id = $one->getCustomerRef();
            $this->repoReg->updateById($id, $one);
        }
    }
}