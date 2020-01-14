<?php
/**
 * Module's configuration (hard-coded).
 *
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund;

use Praxigento\Wallet\Config as WalletCfg;

class Config
    extends \Praxigento\BonusHybrid\Config
{
    const ACL_PENSION_FUND = 'admin_accounts_pension_fund';

    const CODE_TYPE_ASSET_PENSION = 'PENSION';
    const CODE_TYPE_ASSET_WALLET = WalletCfg::CODE_TYPE_ASSET_WALLET;

    const CODE_TYPE_CALC_PENSION = 'PENSION';
    const CODE_TYPE_CALC_PROC_FEE = 'PROC_FEE';

    const CODE_TYPE_OPER_PENSION_CLEANUP = 'PENSION_CLEANUP';
    const CODE_TYPE_OPER_PENSION_INCOME = 'PENSION_INCOME';
    const CODE_TYPE_OPER_PENSION_PERCENT = 'PENSION_PERCENT';
    const CODE_TYPE_OPER_PENSION_RETURN = 'PENSION_RETURN';
    const CODE_TYPE_OPER_PROC_FEE_DEF = 'PROC_FEE_DEF';
    const CODE_TYPE_OPER_PROC_FEE_EU = 'PROC_FEE_EU';

    const DEF_PENSION_INTEREST_PERCENT = 0.03;
    const MENU_PENSION_FUND = self::ACL_PENSION_FUND;
    const MODULE = 'Praxigento_PensionFund';
}
