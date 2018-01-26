<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Fee\Own;

use Praxigento\PensionFund\Config as Cfg;

/**
 * Calculate processing fee using total bonus amounts & customers ranks.
 */
class Calc
{
    const MAX_LEVEL = 100;

    private $mapFees = [
        25 => 0.00,
        50 => 1.00,
        100 => 2.00
    ];
    private $mapRankFee = [
        Cfg::RANK_DISTRIBUTOR => 5,
        Cfg::RANK_MANAGER => 5,
        Cfg::RANK_SEN_MANAGER => 10,
        Cfg::RANK_SUPERVISOR => 15,
        Cfg::RANK_DIRECTOR => 20,
        Cfg::RANK_SEN_DIRECTOR => 25,
        Cfg::RANK_EXEC_DIRECTOR => 30,
        Cfg::RANK_SEN_VICE => 35,
        Cfg::RANK_EXEC_VICE => 40,
        Cfg::RANK_PRESIDENT => 45
    ];
    private $mapRanks;

    /** @var \Praxigento\BonusBase\Repo\Entity\Rank */
    private $repoRank;

    public function __construct(
        \Praxigento\BonusBase\Repo\Entity\Rank $repoRank
    ) {
        $this->repoRank = $repoRank;
    }

    public function exec($totals, $ranks)
    {
        $result = [];
        foreach ($totals as $custId => $amount) {
            if ($amount > self::MAX_LEVEL) {
                /* calc fee for amounts > 100 */
                $rankId = $ranks[$custId];
                $rankCode = $this->getCodeById($rankId);
                $fee = $this->mapRankFee[$rankCode];
            } else {
                /* calc fee for amounts <= 100 */
                foreach ($this->mapFees as $level => $fee) {
                    if ($amount <= $level) break;
                }
            }
            $result[$custId] = $fee;
        }
        return $result;
    }

    /**
     * Get rank code by rank id.
     *
     * @param int $id
     * @return string
     */
    private function getCodeById($id)
    {
        if (is_null($this->mapRanks)) {
            $this->mapRanks = [];
            $rs = $this->repoRank->get();
            /** @var \Praxigento\BonusBase\Repo\Entity\Data\Rank $one */
            foreach ($rs as $one) {
                $rankId = $one->getId();
                $rankCode = $one->getCode();
                $this->mapRanks[$rankId] = $rankCode;
            }
        }
        $result = $this->mapRanks[$id];
        return $result;
    }
}