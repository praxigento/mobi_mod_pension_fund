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
 * Update registry data and create operations for unqualified customers funds return.
 */
class ProcessUnqualified
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

    private function createTransaction($custId, $assetTypeId, $accIdRepres, $amount, $dateAppl, $note)
    {
        $accCust = $this->repoAcc->getByCustomerId($custId, $assetTypeId);
        $accIdCust = $accCust->getId();
        $tranPens = new ETrans();
        $tranPens->setDebitAccId($accIdCust);
        $tranPens->setCreditAccId($accIdRepres);
        $tranPens->setValue($amount);
        $tranPens->setDateApplied($dateAppl);
        $tranPens->setNote($note);
        return $tranPens;
    }

    /**
     * @param \Praxigento\PensionFund\Repo\Entity\Data\Registry[] $registry
     * @param int[] $qualified array with IDs of the qualified customers
     * @param string $period 'YYYYMM'
     * @return int id of the created operation
     * @throws \Exception
     */
    public function exec($registry, $qualified, $period)
    {
        /** define local working data */
        $assetTypeId = $this->repoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_PENSION);
        $accIdRepres = $this->repoAcc->getRepresentativeAccountId($assetTypeId);
        $ds = $this->hlpPeriod->getPeriodLastDate($period);
        $dateApplied = $this->hlpPeriod->getTimestampUpTo($ds);
        $note = "Pension fund cleanup on inactivity (period #$period).";

        /** perform processing */
        $trans = [];
        /** @var \Praxigento\PensionFund\Repo\Entity\Data\Registry $item */
        foreach ($registry as $item) {
            $custId = $item->getCustomerRef();
            if (!in_array($custId, $qualified)) {
                list($updated, $amountClean) = $this->processItem($item, $period);
                $this->repoReg->updateById($custId, $updated);
                if ($amountClean > Cfg::DEF_ZERO) {
                    $trans[] = $this->createTransaction(
                        $custId,
                        $assetTypeId,
                        $accIdRepres,
                        $amountClean,
                        $dateApplied,
                        $note
                    );
                }
            }
        }
        /* create clean up operation if there are inactive customers with balances */
        if (count($trans) > 0) {
            $operId = $this->createOperation(Cfg::CODE_TYPE_OPER_PENSION_CLEANUP, $trans, $note);
        } else {
            $operId = null;
        }

        /** compose result */
        return [$operId];
    }

    /**
     * @param EPensReg $item
     * @param string $period (YYYYMM)
     * @return array [EPensReg, float]
     */
    private function processItem($item, $period)
    {
        $balanceOpen = $item->getBalanceClose();
        $monthsInact = $item->getMonthsInact();
        $monthsLeft = $item->getMonthsLeft();
        $monthsTotal = $item->getMonthsTotal();
        $periodTerm = $item->getPeriodTerm();
        $amountClean = 0; // amount to return to the store.
        if ($monthsInact >= 1) {
            /* this customer already was an inactive in the current period */
            if ($monthsLeft <= 1) {
                /* start next year (inactivity is in the previous period) */
                $monthsLeft = 12;
            }
            $monthsInact++;
            $monthsLeft--;
            $monthsTotal++;
            if (is_null($periodTerm)) {
                $periodTerm = $period;
            }
            $amountClean = $balanceOpen;
            $balanceOpen = 0;
        } else {
            /* this is first inactive event in the period */
            if ($monthsLeft <= 1) {
                /* start next year (inactivity is in the previous period) */
                $monthsLeft = 12;
            }
            $monthsInact = 1;
            $monthsLeft--;
            $monthsTotal++;
        }
        $item->setBalanceOpen($balanceOpen);
        $item->setAmountIn(0);
        $item->setAmountPercent(0);
        $item->setMonthsInact($monthsInact);
        $item->setMonthsLeft($monthsLeft);
        $item->setMonthsTotal($monthsTotal);
        $item->setPeriodTerm($periodTerm);

        /** compose result */
        return [$item, $amountClean];
    }

}