<?php
/**
 * Populate DB schema with module's initial data
 * .
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Setup;

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
        $this->addAccountingOperationsTypes();
        $this->addBonusCalculationsTypes();
    }

    private function addAccountingOperationsTypes()
    {
        $this->_conn->insertArray(
            $this->_resource->getTableName(TypeOperation::ENTITY_NAME),
            [TypeOperation::ATTR_CODE, TypeOperation::ATTR_NOTE],
            [
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
                [Cfg::CODE_TYPE_CALC_PROC_FEE, 'Processing fee calculation.'],
            ]
        );
    }
}