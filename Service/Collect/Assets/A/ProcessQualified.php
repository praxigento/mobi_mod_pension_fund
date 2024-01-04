<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Assets\A;

use Praxigento\Accounting\Api\Service\Operation\Create\Request as AReqOper;
use Praxigento\Accounting\Api\Service\Operation\Create\Response as ARespOper;
use Praxigento\Accounting\Repo\Data\Transaction as ETrans;
use Praxigento\PensionFund\Config as Cfg;
use Praxigento\PensionFund\Repo\Data\Registry as EPensReg;

/**
 * Update registry data and create operations for pension incomes & percents for the period.
 */
class ProcessQualified
{
    /** @var \Praxigento\Accounting\Repo\Dao\Account */
    private $daoAcc;
    /** @var \Praxigento\Accounting\Repo\Dao\Type\Asset */
    private $daoAssetType;
    /** @var \Praxigento\PensionFund\Repo\Dao\Registry */
    private $daoReg;
    /** @var \Praxigento\Core\Api\Helper\Period */
    private $hlpPeriod;
    /** @var \Praxigento\Accounting\Api\Service\Operation\Create */
    private $servOper;

    public function __construct(
        \Praxigento\Accounting\Repo\Dao\Account $daoAcc,
        \Praxigento\PensionFund\Repo\Dao\Registry $daoReg,
        \Praxigento\Accounting\Repo\Dao\Type\Asset $daoAssetType,
        \Praxigento\Core\Api\Helper\Period $hlpPeriod,
        \Praxigento\Accounting\Api\Service\Operation\Create $servOper
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
        $result = null;
        if (count($trans)) {
            $req = new AReqOper();
            $req->setOperationTypeCode($operTypeCode);
            $req->setTransactions($trans);
            $req->setOperationNote($note);
            /** @var ARespOper $resp */
            $resp = $this->servOper->exec($req);
            $result = $resp->getOperationId();
        }
        return $result;
    }

    /**
     * Get "customer ID to pension balance" map.
     * @return array [$custId=>$balance]
     */
    private function getBalancesPension() {
        $result = [];
        $byAccId = $this->daoAcc->getAllByAssetTypeCode(Cfg::CODE_TYPE_ASSET_PENSION);
        foreach ($byAccId as $one) {
            $custId = $one->getCustomerId();
            $balance = $one->getBalance();
            $result[$custId] = $balance;
        }
        return $result;
    }

    /**
     * @param \Praxigento\PensionFund\Repo\Data\Registry[] $registry
     * @param int[] $qual array with IDs of the qualified customers
     * @param int[] $unqual array with IDs of the first time unqualified customers
     * @param float $fee total amount of the processing fee for period
     * @param string $period 'YYYYMM'
     * @return int[] IDs of the created operations ([$operIdPens, $operIdPercent, $operIdReturn])
     * @throws \Exception
     */
    public function exec($registry, $qual, $unqual, $fee, $period)
    {
        /** define local working data */
        $assetPensionTypeId = $this->daoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_PENSION);
        $accPensionIdSys = $this->daoAcc->getSystemAccountId($assetPensionTypeId);
        $assetWalletTypeId = $this->daoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET);
        $accWalletIdSys = $this->daoAcc->getSystemAccountId($assetWalletTypeId);
        $balances = $this->getBalancesPension();
        $ds = $this->hlpPeriod->getPeriodLastDate($period);
        $dateApplied = $this->hlpPeriod->getTimestampUpTo($ds);
        $notePens = "Pension income for period #$period.";
        $notePercent = "Pension percents for period #$period.";
        $noteReturn = "Pension returns for period #$period.";
        /** perform processing */
        $pensioners = array_merge($qual, $unqual);
        $totalCustomers = count($pensioners);
        $income = floor($fee / $totalCustomers * 100) / 100;
        /* prepare registry updates & pension income transactions */
        $updates = [];
        $transPens = [];
        $transPercent = [];
        $transReturn = [];
        foreach ($pensioners as $custId) {
            $isUnqual = in_array($custId, $unqual);
            $update = $this->prepareRegUpdate($custId, $income, $registry, $isUnqual, $period, $balances);
            $updates[] = $update;
            $accCust = $this->daoAcc->getByCustomerId($custId, $assetPensionTypeId);
            $accIdCust = $accCust->getId();
            /* create income transaction */
            $tranPens = new ETrans();
            $tranPens->setDebitAccId($accPensionIdSys);
            $tranPens->setCreditAccId($accIdCust);
            $tranPens->setValue($update->getAmountIn());
            $tranPens->setDateApplied($dateApplied);
            $tranPens->setNote($notePens);
            $transPens[] = $tranPens;
            /* create percent transaction */
            $amntPercent = $update->getAmountPercent();
            if ($amntPercent > Cfg::DEF_ZERO) {
                $tranPercent = new ETrans();
                $tranPercent->setDebitAccId($accPensionIdSys);
                $tranPercent->setCreditAccId($accIdCust);
                $tranPercent->setValue($amntPercent);
                $tranPercent->setDateApplied($dateApplied);
                $tranPercent->setNote($notePercent);
                $transPercent[] = $tranPercent;
            }
            /* create return transaction */
            $amntReturn = $update->getAmountReturned();
            if ($amntReturn > Cfg::DEF_ZERO) {
                /* outgoing transaction from pension account */
                $tranReturn = new ETrans();
                $tranReturn->setDebitAccId($accIdCust);
                $tranReturn->setCreditAccId($accPensionIdSys);
                $tranReturn->setValue($amntReturn);
                $tranReturn->setDateApplied($dateApplied);
                $months = $update->getMonthsTotal(); // see MOBI-1306, SAN-381, SAN-435
                $div = $months % 120; // 10 years
                if ($div == 0) $div = 120;
                $note = "Pension return on $div months.";
                $tranReturn->setNote($note);
                $transReturn[] = $tranReturn;
                /* incoming transaction to wallet account */
                $accWalletCust = $this->daoAcc->getByCustomerId($custId, $assetWalletTypeId);
                $accWalletIdCust = $accWalletCust->getId();
                $tranReturn = new ETrans();
                $tranReturn->setDebitAccId($accWalletIdSys);
                $tranReturn->setCreditAccId($accWalletIdCust);
                $tranReturn->setValue($amntReturn);
                $tranReturn->setDateApplied($dateApplied);
                $tranReturn->setNote($note);
                $transReturn[] = $tranReturn;
            }
        }
        /* create operations */
        $operIdPens = $this->createOperation(Cfg::CODE_TYPE_OPER_PENSION_INCOME, $transPens, $notePens);
        $operIdPercent = $this->createOperation(Cfg::CODE_TYPE_OPER_PENSION_PERCENT, $transPercent, $notePercent);
        $operIdReturn = $this->createOperation(Cfg::CODE_TYPE_OPER_PENSION_RETURN, $transReturn, $noteReturn);
        /* save pension registry updates */
        $this->updateRegistry($updates, $registry);

        /** compose result */
        return [$operIdPens, $operIdPercent, $operIdReturn];
    }

    /**
     * Prepare data to update pension registry for pensioners (qualified & first timers).
     *
     * @param int $custId
     * @param float $amntIn
     * @param \Praxigento\PensionFund\Repo\Data\Registry[] $registry
     * @param bool $isUnqual
     * @param string $period YYYYMM
     * @param array $balances "customer ID to pension balance" map.
     * @return \Praxigento\PensionFund\Repo\Data\Registry
     * @throws \Exception
     */
    private function prepareRegUpdate($custId, $amntIn, $registry, $isUnqual, $period, $balances) {
        if (isset($registry[$custId])) {
            $result = $registry[$custId];
            if (!$isUnqual) {
                $monthsInact = $result->getMonthsInact();
                if ($monthsInact > 1) {
                    /* this is returned customer (was kicked off program before) */
                    $result = $this->resetRegistryEntry($result, $period);
                }
            }
        } else {
            /* initial data for pension registry */
            $result = new EPensReg();
            $result->setCustomerRef($custId);
            $result = $this->resetRegistryEntry($result, $period);
        }
        /* open balance equals to the close balance for the previous period */
        $balanceOpen = (float)($balances[$custId] ?? 0);
        /* 3% per year = 0.03 / 12 per month */
        $amntPercent = floor($balanceOpen * Cfg::DEF_PENSION_INTEREST_PERCENT / 12 * 100) / 100;
        $balanceClose = $balanceOpen + $amntIn + $amntPercent;
        $monthsInact = $result->getMonthsInact();
        $monthsLeft = $result->getMonthsLeft();
        $monthsTotal = $result->getMonthsTotal();
        /* this customer is active in the current period, switch months */
        if ($monthsLeft <= 1) {
            /* start next year */
            $monthsLeft = 12;
            $monthsInact = 0;
        } else {
            $monthsLeft--;
        }
        if ($isUnqual) {
            $monthsInact++;
        }
        $monthsTotal++;

        /* check pension returns ("+-0/1/2" - see MOBI-1306, MOBI-1308, MOBI-1492, SAN-435) */
        $div = $monthsTotal % 120;
        if ($div == 36) {
            $amntReturn = round($balanceClose * 0.3, 2);
            $balanceClose -= $amntReturn;
        } elseif ($div == 72) {
            $amntReturn = round($balanceClose * 0.5, 2);
            $balanceClose -= $amntReturn;
        } elseif (($div == 0) && ($monthsTotal > 0)) {
            $amntReturn = $balanceClose;
            $balanceClose = 0;
        } else {
            $amntReturn = 0;
        }

        /** compose result */
        $result->setBalanceOpen($balanceOpen);
        $result->setAmountIn($amntIn);
        $result->setAmountPercent($amntPercent);
        $result->setAmountReturned($amntReturn);
        $result->setBalanceClose($balanceClose);
        $result->setMonthsInact($monthsInact);
        $result->setMonthsLeft($monthsLeft);
        $result->setMonthsTotal($monthsTotal);
        return $result;
    }

    /**
     * @param \Praxigento\PensionFund\Repo\Data\Registry $entry
     * @param string $period YYYYMM
     * @return \Praxigento\PensionFund\Repo\Data\Registry
     */
    private function resetRegistryEntry($entry, $period)
    {
        $entry->setAmountIn(0);
        $entry->setAmountPercent(0);
        $entry->setAmountReturned(0);
        $entry->setBalanceClose(0);
        $entry->setBalanceOpen(0);
        $entry->setMonthsInact(0);
        $entry->setMonthsLeft(12);
        $entry->setMonthsTotal(0);
        $entry->setPeriodSince($period);
        $entry->setPeriodTerm(null);
        return $entry;
    }
    /**
     * @param \Praxigento\PensionFund\Repo\Data\Registry[] $updates
     * @param \Praxigento\PensionFund\Repo\Data\Registry[] $registry
     */
    private function updateRegistry($updates, $registry)
    {
        /** @var \Praxigento\PensionFund\Repo\Data\Registry $one */
        foreach ($updates as $one) {
            $id = $one->getCustomerRef();
            if (isset($registry[$id])) {
                $this->daoReg->updateById($id, $one);
            } else {
                $this->daoReg->create($one);
            }
        }
    }
}
