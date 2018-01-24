<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Cli\Collect;

/**
 * Collect processing fee for the last calculated period.
 */
class ProcessingFee
    extends \Praxigento\Core\App\Cli\Cmd\Base
{
    /** @var \Praxigento\Core\App\Transaction\Database\IManager */
    private $manTrans;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $manObj,
        \Praxigento\Core\App\Transaction\Database\IManager $manTrans
    ) {
        parent::__construct(
            $manObj,
            'prxgt:pension:collect:fee',
            'Collect processing fee for the last calculated period.'
        );
        $this->manTrans = $manTrans;
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $output->writeln("<info>Start processing fee collection.<info>");
        /* wrap all DB operations with DB transaction */
        $def = $this->manTrans->begin();

        $this->manTrans->rollback($def);
        $output->writeln('<info>Command is completed.<info>');
    }
}