<?php

use Base\SellerPrintSettings as BaseSellerPrintSettings;

/**
 * Skeleton subclass for representing a row from the 'seller_print_settings' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class SellerPrintSettings extends BaseSellerPrintSettings
{
    const int DEFAULT_PAGE_INIT_X = 8;
    const float DEFAULT_PAGE_INIT_Y = 14.5;

    const float DEFAULT_LABEL_SPACE_X = 2.5;
    const float DEFAULT_LABEL_SPACE_Y = 0.3;

    const int DEFAULT_LABEL_HEIGHT = 29;
    const float DEFAULT_LABEL_WIDTH = 63.5;

    public static function initWithDefaults(int $fk_seller_id): SellerPrintSettings
    {
        $settings = new self();
        $settings->setFkSellerId($fk_seller_id);
        $settings->setPageInitX(self::DEFAULT_PAGE_INIT_X);
        $settings->setPageInitY(self::DEFAULT_PAGE_INIT_Y);
        $settings->setLabelSpaceX(self::DEFAULT_LABEL_SPACE_X);
        $settings->setLabelSpaceY(self::DEFAULT_LABEL_SPACE_Y);
        $settings->setLabelWidth(self::DEFAULT_LABEL_WIDTH);
        $settings->setLabelHeight(self::DEFAULT_LABEL_HEIGHT);
        return $settings;
    }

    public function toFlatArray(): array
    {
        $array = array();
        $array['page_init_x'] = $this->getPageInitX();
        $array['page_init_y'] = $this->getPageInitY();
        $array['label_space_x'] = $this->getLabelSpaceX();
        $array['label_space_y'] = $this->getLabelSpaceY();
        $array['label_width'] = $this->getLabelWidth();
        $array['label_height'] = $this->getLabelHeight();
        return $array;
    }
}
