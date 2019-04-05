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
    /** @var \Praxigento\PensionFund\Service\Collect\Fee */
    private $servCollectFee;

    public function __construct(
        \Praxigento\PensionFund\Service\Collect\Fee $servCollectFee
    ) {
        parent::__construct(
            'prxgt:pension:collect:fee',
            'Collect processing fee for the last calculated period.'
        );
        $this->servCollectFee = $servCollectFee;
    }

    protected function process(\Symfony\Component\Console\Input\InputInterface $input)
    {
        $req = new \Praxigento\PensionFund\Service\Collect\Fee\Request();
        $resp = $this->servCollectFee->exec($req);
        $operId = $resp->getOperationId();
        $this->logInfo("Processing fee operation #$operId is created.");

    }
}