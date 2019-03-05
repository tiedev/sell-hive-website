<?php

use Base\Item as BaseItem;
use Map\ItemTableMap;

/**
 * Skeleton subclass for representing a row from the 'item' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Item extends BaseItem
{
    public function toFlatArray()
    {
        $array = array();
        $array['id'] = $this->getId();
        $array['barcode_id'] = $this->getBarcodeId();
        $array['seller'] = $this->getFkSellerId();
        $array['name'] = $this->getName();
        $array['publisher'] = $this->getPublisher();
        $array['price'] = $this->getPrice();
        $array['boxed_as_new'] = $this->getBoxedAsNew();
        $array['comment'] = $this->getComment();
        $array['labeled'] = $this->isLabeled();
        $array['transfered'] = $this->isTransfered();
        $array['sold'] = $this->isSold();
        $array['state'] = $this->getState();
        return $array;
    }

    public function getBarcodeId()
    {
        // TODO get prefix by logic
        return 'B' . $this->getId();
    }

    public function getNameForPdf()
    {
        return substr(utf8_decode($this->getName() . ' (' . $this->getPublisher() . ')'), 0, 60);
    }

    public function getPriceForPdf()
    {
        return sprintf('%01.2f', $this->getPrice() / 100) . ' ' . chr(128);
    }

    public function isLabeled()
    {
        return isset($this->labeled);
    }

    public function isTransfered()
    {
        return isset($this->transfered);
    }

    public function isSold()
    {
        return isset($this->sold);
    }

    public function setLabeledToNull()
    {
        $this->labeled = null;
        $this->modifiedColumns[ItemTableMap::COL_LABELED] = true;
    }

    public function getState()
    {
        if ($this->isSold()) {
            return 'sold';
        } elseif ($this->isTransfered()) {
            return 'transfered';
        } elseif ($this->isLabeled()) {
            return 'labeled';
        } else {
            return 'created';
        }
    }
}
