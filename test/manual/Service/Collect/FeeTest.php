<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Test\Praxigento\PensionFund\Service\Collect\Fee;

use Praxigento\PensionFund\Service\Collect\Fee as AService;
use Praxigento\PensionFund\Service\Collect\Fee\Request as ARequest;
use Praxigento\PensionFund\Service\Collect\Fee\Response as AResponse;

include_once(__DIR__ . '/../../phpunit_bootstrap.php');

class FeeTest
    extends \Praxigento\Core\Test\BaseCase\Manual
{


    public function test_exec()
    {
        $def = $this->manTrans->begin();
        /** @var  $serv AService */
        $serv = $this->manObj->get(AService::class);
        $req = new ARequest();
        $resp = $serv->exec($req);
        $this->assertTrue($resp instanceof AResponse);
        $this->manTrans->rollback($def);
    }

}