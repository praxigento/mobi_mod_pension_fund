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
    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private $conn;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Praxigento\PensionFund\Service\Collect\Fee */
    private $servCollectFee;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $manObj,
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\PensionFund\Service\Collect\Fee $servCollectFee
    ) {
        parent::__construct(
            $manObj,
            'prxgt:pension:collect:fee',
            'Collect processing fee for the last calculated period.'
        );
        $this->resource = $resource;
        $this->conn = $resource->getConnection();
        $this->servCollectFee = $servCollectFee;
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $output->writeln("<info>Start processing fee collection.<info>");
        /* wrap all DB operations with DB transaction */
        $this->conn->beginTransaction();
        try {

        $req = new \Praxigento\PensionFund\Service\Collect\Fee\Request();
        $resp = $this->servCollectFee->exec($req);
        $operId = $resp->getOperationId();
        $output->writeln("<info>Processing fee operation #$operId is created.<info>");

            $this->conn->commit();
        } catch (\Throwable $e) {
            $output->writeln('<info>Command \'' . $this->getName() . '\' failed. Reason: '
                . $e->getMessage() . '.<info>');
            $this->conn->rollBack();
        }
        $output->writeln('<info>Command \'' . $this->getName() . '\' is completed.<info>');
    }
}