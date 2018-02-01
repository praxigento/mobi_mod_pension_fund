<?php
/**
 * Module's configuration (hard-coded).
 *
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund;

class Config
    extends \Praxigento\BonusHybrid\Config
{
    const CODE_TYPE_ASSET_PENSION = 'PENSION';
    const CODE_TYPE_CALC_PENSION = 'PENSION';
    const CODE_TYPE_CALC_PENSION_PERCENT = 'PENSION_PERCENT';
    const CODE_TYPE_CALC_PROC_FEE = 'PROC_FEE';
    const CODE_TYPE_OPER_PENSION = 'PENSION';
    const CODE_TYPE_OPER_PENSION_PERCENT = 'PENSION_PERCENT';
    const CODE_TYPE_OPER_PROC_FEE = 'PROC_FEE';
    const MODULE = 'Praxigento_PensionFund';
}