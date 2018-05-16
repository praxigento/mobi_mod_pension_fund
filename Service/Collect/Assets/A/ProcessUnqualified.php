<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Assets\A;

use Praxigento\Accounting\Api\Service\Operation\Request as AReqOper;
use Praxigento\Accounting\Api\Service\Operation\Response as ARespOper;
use Praxigento\Accounting\Repo\Data\Transaction as ETrans;
use Praxigento\PensionFund\Config as Cfg;
use Praxigento\PensionFund\Repo\Data\Registry as EPensReg;

/**
 * Update registry data and create operations for unqualified customers funds return.
 */
class ProcessUnqualified
{
    /** @var \Praxigento\Core\Api\Helper\Period */
    private $hlpPeriod;
    /** @var \Praxigento\Accounting\Repo\Dao\Account */
    private $daoAcc;
    /** @var \Praxigento\Accounting\Repo\Dao\Type\Asset */
    private $daoAssetType;
    /** @var \Praxigento\PensionFund\Repo\Dao\Registry */
    private $daoReg;
    /** @var \Praxigento\Accounting\Api\Service\Operation */
    private $servOper;

    public function __construct(
        \Praxigento\Accounting\Repo\Dao\Account $daoAcc,
        \Praxigento\PensionFund\Repo\Dao\Registry $daoReg,
        \Praxigento\Accounting\Repo\Dao\Type\Asset $daoAssetType,
        \Praxigento\Core\Api\Helper\Period $hlpPeriod,
        \Praxigento\Accounting\Api\Service\Operation $servOper
    ) {
        $this->daoAcc = $daoAcc;
        $this->daoReg = $daoReg;
        $this->daoAssetType = $daoAssetType;
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

    private function createTransaction($custId, $assetTypeId, $accIdSys, $amount, $dateAppl, $note)
    {
        $accCust = $this->daoAcc->getByCustomerId($custId, $assetTypeId);
        $accIdCust = $accCust->getId();
        $tranPens = new ETrans();
        $tranPens->setDebitAccId($accIdCust);
        $tranPens->setCreditAccId($accIdSys);
        $tranPens->setValue($amount);
        $tranPens->setDateApplied($dateAppl);
        $tranPens->setNote($note);
        return $tranPens;
    }

    /**
     * @param \Praxigento\PensionFund\Repo\Data\Registry[] $registry
     * @param int[] $qual array with IDs of the qualified customers
     * @param int[] $unqual array with IDs of the unqualified pensioners (first timers)
     * @param string $period 'YYYYMM'
     * @return int id of the created operation
     * @throws \Exception
     */
    public function exec($registry, $qual, $unqual, $period)
    {
        /** define local working data */
        $assetTypeId = $this->daoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_PENSION);
        $accIdSys = $this->daoAcc->getSystemAccountId($assetTypeId);
        $ds = $this->hlpPeriod->getPeriodLastDate($period);
        $dateApplied = $this->hlpPeriod->getTimestampUpTo($ds);
        $note = "Pension fund cleanup on inactivity (period #$period).";
        $pensioners = array_merge($qual, $unqual);

        /** perform processing */
        $trans = [];
        /** @var \Praxigento\PensionFund\Repo\Data\Registry $item */
        foreach ($registry as $item) {
            $custId = $item->getCustomerRef();
            if (!in_array($custId, $pensioners)) {
                list($updated, $amountClean) = $this->processRegistryItem($item, $period);
                $this->daoReg->updateById($custId, $updated);
                if ($amountClean > Cfg::DEF_ZERO) {
                    $trans[] = $this->createTransaction(
                        $custId,
                        $assetTypeId,
                        $accIdSys,
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
    private function processRegistryItem($item, $period)
    {
        $balance = $item->getBalanceClose();
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
            $amountClean = $balance;
        }
        $item->setBalanceOpen(0);
        $item->setBalanceClose(0);
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