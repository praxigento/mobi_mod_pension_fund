<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Assets\A;

use Praxigento\BonusBase\Repo\Data\Rank as ERank;
use Praxigento\Downline\Repo\Data\Customer as EDwnlCust;
use Praxigento\PensionFund\Config as Cfg;

/**
 * Get list of qualified customers for given calculation. EU customers are not participated in pension program.
 */
class GetQualified
{
    /** @var int[] array of the qualified ranks IDs */
    private $cacheQualRanks;
    /** @var \Praxigento\BonusHybrid\Repo\Dao\Downline */
    private $daoBonDwnl;
    /** @var \Praxigento\Downline\Repo\Dao\Customer */
    private $daoDwnlCust;
    /** @var \Praxigento\BonusBase\Repo\Dao\Rank */
    private $daoRank;
    /** @var \Praxigento\PensionFund\Service\Collect\Z\GetEuCustomers */
    private $fnGetEuCust;

    public function __construct(
        \Praxigento\BonusBase\Repo\Dao\Rank $daoRank,
        \Praxigento\BonusHybrid\Repo\Dao\Downline $daoBonDwnl,
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnlCust,
        \Praxigento\PensionFund\Service\Collect\Z\GetEuCustomers $fnGetEuCust
    ) {
        $this->daoRank = $daoRank;
        $this->daoBonDwnl = $daoBonDwnl;
        $this->daoDwnlCust = $daoDwnlCust;
        $this->fnGetEuCust = $fnGetEuCust;
    }

    /**
     * Get list of the qualified customers (except EU customers).
     *
     * @param int $calcId phase1 compression calculation ID.
     * @return int[] customers ids.
     */
    public function exec($calcId)
    {
        $reqular = [];
        $tree = $this->daoBonDwnl->getByCalcId($calcId);
        $euCusts = $this->fnGetEuCust->exec();
        foreach ($tree as $one) {
            $custId = $one->getCustomerRef();
            $isEuCust = in_array($custId, $euCusts);
            if (!$isEuCust) {
                $rankId = $one->getRankRef();
                if ($this->isQualified($rankId)) {
                    $custId = $one->getCustomerRef();
                    $reqular[] = $custId;
                }
            }
        }
        $gold = $this->getGoldMembers();
        $result = array_merge($reqular, $gold);
        return $result;
    }

    /**
     * Get customer IDs for gold members (MOBI-1310).
     *
     * @return int[]
     */
    private function getGoldMembers()
    {
        $result = [];
        $where = EDwnlCust::A_MLM_ID . '="777038763"';
        $where .= ' OR ' . EDwnlCust::A_MLM_ID . '="777104780"';
        $where .= ' OR ' . EDwnlCust::A_MLM_ID . '="100002146"';
        $rs = $this->daoDwnlCust->get($where);
        /** @var EDwnlCust $one */
        foreach ($rs as $one) {
            $id = $one->getCustomerRef();
            $result[] = $id;
        }
        return $result;
    }
    /**
     * Collect IDs of the pension qualified ranks and save its to the cache.
     * @return int[]
     */
    private function getQualRanks()
    {
        if (is_null($this->cacheQualRanks)) {
            $this->cacheQualRanks = [];
            $all = $this->daoRank->get();
            /** @var ERank $one */
            foreach ($all as $one) {
                $code = $one->getCode();
                if (
                    (Cfg::RANK_SEN_MANAGER == $code) ||
                    (Cfg::RANK_SUPERVISOR == $code) ||
                    (Cfg::RANK_DIRECTOR == $code) ||
                    (Cfg::RANK_SEN_DIRECTOR == $code) ||
                    (Cfg::RANK_EXEC_DIRECTOR == $code) ||
                    (Cfg::RANK_SEN_VICE == $code) ||
                    (Cfg::RANK_EXEC_VICE == $code) ||
                    (Cfg::RANK_PRESIDENT == $code)
                ) {
                    $this->cacheQualRanks[] = $one->getId();
                }
            }
        }
        return $this->cacheQualRanks;
    }

    /**
     * 'true' if rank is qualified to the pension program.
     *
     * @param int $rankId
     * @return bool
     */
    private function isQualified($rankId)
    {
        $result = false;
        $qualified = $this->getQualRanks();
        if (in_array($rankId, $qualified)) {
            $result = true;
        }
        return $result;
    }
}
