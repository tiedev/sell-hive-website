<?php

abstract class UserLabelPdf extends UserPdf
{
    const ITEMS_PER_COLUMN = 9;
    const ITEMS_PER_ROW = 3;
    const ITEMS_PER_PAGE = self::ITEMS_PER_COLUMN * self::ITEMS_PER_ROW;

    protected $currentIndex;

    protected $settings;

    public function __construct($logger, $type)
    {
        parent::__construct($logger, $type);
    }

    public function setSeller($id)
    {
        parent::setSeller($id);

        $this->logger->debug('init print settings');
        $this->settings = SellerPrintSettingsQuery::create()->getOneOrDefaultByFkSellerId($this->seller->getId());
    }

    protected function x()
    {
        $page_init_x = $this->settings->getPageInitX();
        $label_space_x = $this->settings->getLabelSpaceX();
        $label_width =  $this->settings->getLabelWidth();
        return $page_init_x + ($this->currentIndex % self::ITEMS_PER_ROW) * ($label_space_x + $label_width);
    }

    protected function y()
    {
        $page_init_y = $this->settings->getPageInitY();
        $label_space_y = $this->settings->getLabelSpaceY();
        $label_height = $this->settings->getLabelHeight();
        return $page_init_y + floor($this->currentIndex / self::ITEMS_PER_ROW) * ($label_space_y + $label_height);
    }
}
