<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Service\Collect;

use Praxigento\PensionFund\Service\Collect\Fee\Request as ARequest;
use Praxigento\PensionFund\Service\Collect\Fee\Response as AResponse;

class Fee
{
    /** @var \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent */
    private $servCalcDep;

    public function __construct(
        \Praxigento\BonusBase\Api\Service\Period\Calc\Get\Dependent $servCalcDep
    ) {
        $this->servCalcDep = $servCalcDep;
    }


    /**
     * @param ARequest $request
     * @return AResponse
     */
    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);

        /** perform processing */

        /** compose result */
        $result = new AResponse();
        return $result;
    }

}