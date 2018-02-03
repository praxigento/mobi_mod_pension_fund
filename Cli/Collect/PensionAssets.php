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
    /** @var \Praxigento\Core\App\Transaction\Database\IManager */
    private $manTrans;
    /** @var \Praxigento\PensionFund\Service\Collect\Assets */
    private $servCollectAssets;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $manObj,
        \Praxigento\Core\App\Transaction\Database\IManager $manTrans,
        \Praxigento\PensionFund\Service\Collect\Assets $servCollectAssets
    ) {
        parent::__construct(
            $manObj,
            'prxgt:pension:collect:assets',
            'Collect pension related data and add assets to pension accounts for the last calculated period.'
        );
        $this->manTrans = $manTrans;
        $this->servCollectAssets = $servCollectAssets;
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $output->writeln("<info>Start pension assets collection.<info>");
        /* wrap all DB operations with DB transaction */
        $def = $this->manTrans->begin();

        $req = new \Praxigento\PensionFund\Service\Collect\Assets\Request();
        $resp = $this->servCollectAssets->exec($req);
//        $operId = $resp->getOperationId();
//        $output->writeln("<info>Processing fee operation #$operId is created.<info>");

        $this->manTrans->rollback($def);
        $output->writeln('<info>Command is completed.<info>');
    }
}