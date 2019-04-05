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
    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private $conn;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Praxigento\PensionFund\Service\Collect\Assets */
    private $servCollectAssets;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $manObj,
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\PensionFund\Service\Collect\Assets $servCollectAssets
    ) {
        parent::__construct(
            $manObj,
            'prxgt:pension:collect:assets',
            'Collect pension related data and add assets to pension accounts for the last calculated period.'
        );
        $this->resource = $resource;
        $this->conn = $resource->getConnection();
        $this->servCollectAssets = $servCollectAssets;
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $output->writeln("<info>Command '" . $this->getName() . "' is started.<info>");
        /* wrap all DB operations with DB transaction */
        $this->conn->beginTransaction();
        try {
            $req = new \Praxigento\PensionFund\Service\Collect\Assets\Request();
            $resp = $this->servCollectAssets->exec($req);
            $operIdIncome = $resp->getOperIdIncome();
            $operIdPercent = $resp->getOperIdPercent();
            $operIdReturn = $resp->getOperIdReturn();
            $operIdCleanup = $resp->getOperIdCleanup();
            $msg = "(income: #$operIdIncome, percent: #$operIdPercent, return: #$operIdReturn, cleanup: #$operIdCleanup)";
            $output->writeln(
                "<info>Pension assets processing operations $msg are created.<info>"
            );

            $this->conn->commit();
        } catch (\Throwable $e) {
            $output->writeln('<info>Command \'' . $this->getName() . '\' failed. Reason: '
                . $e->getMessage() . '.<info>');
            $this->conn->rollBack();
        }
        $output->writeln('<info>Command \'' . $this->getName() . '\' is completed.<info>');
    }
}