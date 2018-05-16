<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Fee\A;

use Praxigento\Accounting\Api\Service\Operation\Request as AReqOper;
use Praxigento\Accounting\Api\Service\Operation\Response as ARespOper;
use Praxigento\Accounting\Repo\Data\Transaction as ETrans;
use Praxigento\PensionFund\Config as Cfg;

/**
 * Create operation with transactions for processing fee.
 */
class CreateOperation
{
    /** @var \Praxigento\Core\Api\Helper\Period */
    private $hlpPeriod;
    /** @var \Praxigento\Accounting\Repo\Dao\Account */
    private $daoAcc;
    /** @var \Praxigento\Accounting\Repo\Dao\Type\Asset */
    private $daoAssetType;
    /** @var \Praxigento\Accounting\Api\Service\Operation */
    private $servOper;

    public function __construct(
        \Praxigento\Accounting\Repo\Dao\Account $daoAcc,
        \Praxigento\Accounting\Repo\Dao\Type\Asset $daoAssetType,
        \Praxigento\Core\Api\Helper\Period $hlpPeriod,
        \Praxigento\Accounting\Api\Service\Operation $servOper
    ) {
        $this->daoAcc = $daoAcc;
        $this->daoAssetType = $daoAssetType;
        $this->hlpPeriod = $hlpPeriod;
        $this->servOper = $servOper;
    }

    /**
     * @param array $fees
     * @param string $period 'YYYYMM'
     * @param string $operTypeCode
     * @return int id of the created operation
     * @throws \Exception
     */
    public function exec($fees, $period, $operTypeCode)
    {
        $assetTypeId = $this->daoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET);
        $accIdSys = $this->daoAcc->getSystemAccountId($assetTypeId);
        $ds = $this->hlpPeriod->getPeriodLastDate($period);
        $dateApplied = $this->hlpPeriod->getTimestampUpTo($ds);
        $note = "Processing fee for period #$period.";
        /* prepare bonus & fee transactions */
        $trans = [];
        foreach ($fees as $custId => $amount) {
            $accCust = $this->daoAcc->getByCustomerId($custId, $assetTypeId);
            $accIdCust = $accCust->getId();
            $tran = new ETrans();
            $tran->setDebitAccId($accIdCust);
            $tran->setCreditAccId($accIdSys);
            $tran->setValue($amount);
            $tran->setDateApplied($dateApplied);
            $trans[] = $tran;
        }
        /* create operation */
        $req = new AReqOper();
        $req->setOperationTypeCode($operTypeCode);
        $req->setTransactions($trans);
        $req->setOperationNote($note);
        /** @var ARespOper $resp */
        $resp = $this->servOper->exec($req);
        $result = $resp->getOperationId();
        return $result;
    }
}