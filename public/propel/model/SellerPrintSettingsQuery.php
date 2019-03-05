<?php

use Base\SellerPrintSettingsQuery as BaseSellerPrintSettingsQuery;

/**
 * Skeleton subclass for performing query and update operations on the 'seller_print_settings' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class SellerPrintSettingsQuery extends BaseSellerPrintSettingsQuery
{
    public function getOneOrDefaultByFkSellerId(int $fk_seller_id)
    {
        $settings = $this->findOneByFkSellerId($fk_seller_id);
        return $settings != null ? $settings : SellerPrintSettings::initWithDefaults($fk_seller_id);
    }
}
