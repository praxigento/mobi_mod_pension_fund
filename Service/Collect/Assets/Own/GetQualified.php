<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Assets\Own;

use Praxigento\BonusBase\Repo\Entity\Data\Rank as ERank;
use Praxigento\PensionFund\Config as Cfg;

/**
 * Get list of qualified customers for given calculation. EU customers are not participated in pension program.
 */
class GetQualified
{
    /** @var int[] array of the qualified ranks IDs */
    private $cacheQualRanks;
    /** @var \Praxigento\PensionFund\Service\Collect\A\GetEuCustomers */
    private $fnGetEuCust;
    /** @var \Praxigento\BonusHybrid\Repo\Entity\Downline */
    private $repoBonDwnl;
    /** @var \Praxigento\Downline\Repo\Entity\Customer */
    private $repoDwnlCust;
    /** @var \Praxigento\BonusBase\Repo\Entity\Rank */
    private $repoRank;

    public function __construct(
        \Praxigento\BonusBase\Repo\Entity\Rank $repoRank,
        \Praxigento\BonusHybrid\Repo\Entity\Downline $repoBonDwnl,
        \Praxigento\Downline\Repo\Entity\Customer $repoDwnlCust,
        \Praxigento\PensionFund\Service\Collect\A\GetEuCustomers $fnGetEuCust
    ) {
        $this->repoRank = $repoRank;
        $this->repoBonDwnl = $repoBonDwnl;
        $this->repoDwnlCust = $repoDwnlCust;
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
        $result = [];
        $tree = $this->repoBonDwnl->getByCalcId($calcId);
        $euCusts = $this->fnGetEuCust->exec();
        foreach ($tree as $one) {
            $custId = $one->getCustomerRef();
            $isEuCust = in_array($custId, $euCusts);
            if (!$isEuCust) {
                $rankId = $one->getRankRef();
                if ($this->isQualified($rankId)) {
                    $custId = $one->getCustomerRef();
                    $result[] = $custId;
                }
            }
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
            $all = $this->repoRank->get();
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