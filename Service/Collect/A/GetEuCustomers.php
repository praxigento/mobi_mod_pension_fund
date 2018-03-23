<?php
/**
 * Authors: Alex Gusev <flancer64@gmail.com>
 * Since: 2018
 */

namespace Praxigento\PensionFund\Service\Collect\A;

use Praxigento\Downline\Repo\Data\Customer as EDwnlCust;
use Praxigento\PensionFund\Config as Cfg;

/**
 * Get all EU customers.
 */
class GetEuCustomers
{
    /** @var \Praxigento\BonusHybrid\Helper\IScheme */
    private $hlpScheme;
    /** @var \Praxigento\Downline\Repo\Dao\Customer */
    private $daoDwnlCust;

    public function __construct(
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnlCust,
        \Praxigento\BonusHybrid\Helper\IScheme $hlpScheme
    ) {
        $this->daoDwnlCust = $daoDwnlCust;
        $this->hlpScheme = $hlpScheme;
    }

    /**
     * @return array IDs of the EU customers
     */
    public function exec()
    {
        $result = [];
        $all = $this->daoDwnlCust->get();
        /** @var EDwnlCust $one */
        foreach ($all as $one) {
            $custId = $one->getCustomerId();
            $scheme = $this->hlpScheme->getSchemeByCustomer($one);
            if (Cfg::SCHEMA_EU == $scheme) {
                $result[] = $custId;
            }
        }
        return $result;
    }
}