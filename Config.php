<?php
/**
 * Module's configuration (hard-coded).
 *
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund;

use Praxigento\Accounting\Config as AccCfg;
use Praxigento\BonusReferral\Config as BonRefCfg;
use Praxigento\Pv\Config as PvCfg;
use Praxigento\Wallet\Config as WalletCfg;

class Config
    extends \Praxigento\BonusHybrid\Config
{
    const ACL_PENSION_FUND = 'admin_accounts_pension_fund';

    const CODE_TYPE_ASSET_PENSION = 'PENSION';
    const CODE_TYPE_ASSET_WALLET = WalletCfg::CODE_TYPE_ASSET_WALLET;

    const CODE_TYPE_CALC_PENSION = 'PENSION';
    const CODE_TYPE_CALC_PROC_FEE = 'PROC_FEE';

    const CODE_TYPE_OPER_BONUS_REF_BOUNTY = BonRefCfg::CODE_TYPE_OPER_BONUS_REF_BOUNTY;
    const CODE_TYPE_OPER_BONUS_REF_FEE = BonRefCfg::CODE_TYPE_OPER_BONUS_REF_FEE;
    const CODE_TYPE_OPER_CHANGE_BALANCE = AccCfg::CODE_TYPE_OPER_CHANGE_BALANCE;
    const CODE_TYPE_OPER_PENSION_INCOME = 'PENSION_INCOME';
    const CODE_TYPE_OPER_PENSION_CLEANUP = 'PENSION_CLEANUP';
    const CODE_TYPE_OPER_PENSION_PERCENT = 'PENSION_PERCENT';
    const CODE_TYPE_OPER_PENSION_RETURN = 'PENSION_RETURN';
    const CODE_TYPE_OPER_PROC_FEE_DEF = 'PROC_FEE_DEF';
    const CODE_TYPE_OPER_PROC_FEE_EU = 'PROC_FEE_EU';
    const CODE_TYPE_OPER_PV_TRANSFER = PvCfg::CODE_TYPE_OPER_PV_TRANSFER;
    const CODE_TYPE_OPER_WALLET_SALE = WalletCfg::CODE_TYPE_OPER_WALLET_SALE;

    const DEF_PENSION_INTEREST_PERCENT = 0.03;
    const MENU_PENSION_FUND = self::ACL_PENSION_FUND;
    const MODULE = 'Praxigento_PensionFund';
}
