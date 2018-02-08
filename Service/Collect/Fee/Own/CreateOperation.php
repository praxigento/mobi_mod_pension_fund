<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Fee\Own;

use Praxigento\Accounting\Api\Service\Operation\Request as AReqOper;
use Praxigento\Accounting\Api\Service\Operation\Response as ARespOper;
use Praxigento\Accounting\Repo\Entity\Data\Transaction as ETrans;
use Praxigento\PensionFund\Config as Cfg;

/**
 * Create operation with transactions for processing fee.
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
     * @param array $fees
     * @param string $period 'YYYYMM'
     * @param string $operTypeCode
     * @return int id of the created operation
     * @throws \Exception
     */
    public function exec($fees, $period, $operTypeCode)
    {
        $assetTypeId = $this->repoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET);
        $accIdRepres = $this->repoAcc->getRepresentativeAccountId($assetTypeId);
        $ds = $this->hlpPeriod->getPeriodLastDate($period);
        $dateApplied = $this->hlpPeriod->getTimestampUpTo($ds);
        /* prepare bonus & fee transactions */
        $trans = [];
        foreach ($fees as $custId => $amount) {
            $accCust = $this->repoAcc->getByCustomerId($custId, $assetTypeId);
            $accIdCust = $accCust->getId();
            $tran = new ETrans();
            $tran->setDebitAccId($accIdCust);
            $tran->setCreditAccId($accIdRepres);
            $tran->setValue($amount);
            $tran->setDateApplied($dateApplied);
            $note = "Processing fee for period #$period.";
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