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
    /** @var \Praxigento\Accounting\Repo\Entity\Account */
    private $repoAcc;
    /** @var \Praxigento\Accounting\Repo\Entity\Type\Asset */
    private $repoAssetType;
    /** @var \Praxigento\Accounting\Api\Service\Operation */
    private $servOper;

    public function __construct(
        \Praxigento\Accounting\Repo\Entity\Account $repoAcc,
        \Praxigento\Accounting\Repo\Entity\Type\Asset $repoAssetType,
        \Praxigento\Accounting\Api\Service\Operation $servOper
    ) {
        $this->repoAcc = $repoAcc;
        $this->repoAssetType = $repoAssetType;
        $this->servOper = $servOper;
    }

    /**
     * @param array $fees
     * @param string $period
     * @return int id of the created operation
     * @throws \Exception
     */
    public function exec($fees, $period)
    {
        $assetTypeId = $this->repoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET);
        $accIdRepres = $this->repoAcc->getRepresentativeAccountId($assetTypeId);
        /* prepare bonus & fee transactions */
        $trans = [];
        foreach ($fees as $custId => $amount) {
            $accCust = $this->repoAcc->getByCustomerId($custId, $assetTypeId);
            $accIdCust = $accCust->getId();
            $tranBonus = new ETrans();
            $tranBonus->setDebitAccId($accIdCust);
            $tranBonus->setCreditAccId($accIdRepres);
            $tranBonus->setValue($amount);
            $note = "Processing fee for period #$period.";
            $operType = Cfg::CODE_TYPE_OPER_PROC_FEE;
            $trans[] = $tranBonus;
        }
        /* create operation */
        $req = new AReqOper();
        $req->setOperationTypeCode($operType);
        $req->setTransactions($trans);
        $req->setOperationNote($note);
        /** @var ARespOper $resp */
        $resp = $this->servOper->exec($req);
        $result = $resp->getOperationId();
        return $result;
    }
}