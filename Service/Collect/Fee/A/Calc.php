<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect\Fee\A;

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

    /** @var \Praxigento\BonusBase\Repo\Dao\Rank */
    private $daoRank;

    public function __construct(
        \Praxigento\BonusBase\Repo\Dao\Rank $daoRank
    ) {
        $this->daoRank = $daoRank;
    }

    /**
     * @param array $totals [custId => totalBonusAmnt]
     * @param array $ranks [custId => rankId]
     * @param array $euCusts IDs of the EU customers
     * @return array [$feeDef, $feeEu]
     */
    public function exec($totals, $ranks, $euCusts)
    {
        $feeDef = [];
        $feeEu = [];
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
            if ($fee > Cfg::DEF_ZERO) {
                $isEuCust = in_array($custId, $euCusts);
                if ($isEuCust) {
                    $feeEu[$custId] = $fee;
                } else {
                    $feeDef[$custId] = $fee;
                }
            }
        }
        return [$feeDef, $feeEu];
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
            $rs = $this->daoRank->get();
            /** @var \Praxigento\BonusBase\Repo\Data\Rank $one */
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