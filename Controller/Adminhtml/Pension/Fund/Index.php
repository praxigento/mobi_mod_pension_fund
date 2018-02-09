<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Controller\Adminhtml\Pension\Fund;

use Praxigento\PensionFund\Config as Cfg;

class Index
    extends \Praxigento\Core\App\Action\Back\Base
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    )
    {
        $aclResource = Cfg::MODULE . '::' . Cfg::ACL_PENSION_FUND;
        $activeMenu = Cfg::MODULE . '::' . Cfg::MENU_PENSION_FUND;
        $breadcrumbLabel = 'Pension Fund';
        $breadcrumbTitle = 'Pension Fund';
        $pageTitle = 'Pension Fund';
        parent::__construct(
            $context,
            $aclResource,
            $activeMenu,
            $breadcrumbLabel,
            $breadcrumbTitle,
            $pageTitle
        );
    }
}