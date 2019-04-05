<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Cli\Collect;

/**
 * Collect pension related data and add assets to pension accounts for the last calculated period.
 */
class PensionAssets
    extends \Praxigento\Core\App\Cli\Cmd\Base
{
    /** @var \Praxigento\PensionFund\Service\Collect\Assets */
    private $servCollectAssets;

    public function __construct(
        \Praxigento\PensionFund\Service\Collect\Assets $servCollectAssets
    ) {
        parent::__construct(
            'prxgt:pension:collect:assets',
            'Collect pension related data and add assets to pension accounts for the last calculated period.'
        );
        $this->servCollectAssets = $servCollectAssets;
    }

    protected function process(\Symfony\Component\Console\Input\InputInterface $input)
    {
        $req = new \Praxigento\PensionFund\Service\Collect\Assets\Request();
        $resp = $this->servCollectAssets->exec($req);
        $operIdIncome = $resp->getOperIdIncome();
        $operIdPercent = $resp->getOperIdPercent();
        $operIdReturn = $resp->getOperIdReturn();
        $operIdCleanup = $resp->getOperIdCleanup();
        $msg = "(income: #$operIdIncome, percent: #$operIdPercent, return: #$operIdReturn, cleanup: #$operIdCleanup)";
        $this->logInfo("Pension assets processing operations $msg are created.");
    }
}