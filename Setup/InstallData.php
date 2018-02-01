<?php
/**
 * Populate DB schema with module's initial data
 * .
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Setup;

use Praxigento\Accounting\Repo\Entity\Data\Type\Asset as TypeAsset;
use Praxigento\Accounting\Repo\Entity\Data\Type\Operation as TypeOperation;
use Praxigento\BonusBase\Repo\Entity\Data\Type\Calc as TypeCalc;
use Praxigento\PensionFund\Config as Cfg;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class InstallData
    extends \Praxigento\Core\App\Setup\Data\Base
{
    protected function _setup()
    {
        $this->addAccountingAssetsTypes();
        $this->addAccountingOperationsTypes();
        $this->addBonusCalculationsTypes();
    }

    private function addAccountingAssetsTypes()
    {
        $this->_conn->insertArray(
            $this->_resource->getTableName(TypeAsset::ENTITY_NAME),
            [TypeAsset::ATTR_CODE, TypeAsset::ATTR_NOTE, TypeAsset::ATTR_IS_VISIBLE],
            [
                [
                    Cfg::CODE_TYPE_ASSET_PENSION,
                    'Pension funds. Programmatically processing only.',
                    false
                ]
            ]
        );
    }

    private function addAccountingOperationsTypes()
    {
        $this->_conn->insertArray(
            $this->_resource->getTableName(TypeOperation::ENTITY_NAME),
            [TypeOperation::ATTR_CODE, TypeOperation::ATTR_NOTE],
            [
                [Cfg::CODE_TYPE_OPER_PENSION, 'Pension funds payments.'],
                [Cfg::CODE_TYPE_OPER_PENSION_PERCENT, 'Pension funds interest payments.'],
                [Cfg::CODE_TYPE_OPER_PROC_FEE, 'Processing fee.']
            ]
        );
    }

    private function addBonusCalculationsTypes()
    {
        $this->_conn->insertArray(
            $this->_resource->getTableName(TypeCalc::ENTITY_NAME),
            [TypeCalc::ATTR_CODE, TypeCalc::ATTR_NOTE],
            [
                [Cfg::CODE_TYPE_CALC_PENSION, 'Pension funds payments calculation.'],
                [Cfg::CODE_TYPE_CALC_PENSION_PERCENT, 'Pension funds interest calculation.'],
                [Cfg::CODE_TYPE_CALC_PROC_FEE, 'Processing fee calculation.']
            ]
        );
    }
}