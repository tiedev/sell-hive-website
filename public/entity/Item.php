<?php

namespace Entity;

/**
 * @OA\Schema(@OA\Xml(name="Item"))
 */
class Item
{
    /**
     * @OA\Property()
     * @var int
     */
    public $id;

    /**
     * @OA\Property()
     * @var string
     */
    public $barcode_id;

    /**
     * @OA\Property()
     * @var int
     */
    public $seller;

    /**
     * @OA\Property()
     * @var string
     */
    public $name;

    /**
     * @OA\Property()
     * @var string
     */
    public $publisher;

    /**
     * @OA\Property()
     * @var int
     */
    public $price;

    /**
     * @OA\Property(default=false)
     * @var bool
     */
    public $boxed_as_new;

    /**
     * @OA\Property()
     * @var string
     */
    public $comment;

    /**
     * @OA\Property(default=false)
     * @var bool
     */
    public $labeled;

    /**
     * @OA\Property(default=false)
     * @var bool
     */
    public $transfered;

    /**
     * @var bool
     * Is item sold?
     * @OA\Property(default=false)
     */
    public $sold;

    /**
     * current item state
     * @OA\Property(enum={"created", "labeled", "transfered", "sold"})
     * @var string
     */
    public $state;

    public function __construct($item)
    {
        $this->id = $item->getId();
        $this->barcode_id = $item->getBarcodeId();
        $this->seller = $item->getFkSellerId();
        $this->name = $item->getName();
        $this->publisher = $item->getPublisher();
        $this->price = $item->getPrice();
        $this->boxed_as_new = $item->getBoxedAsNew();
        $this->comment = $item->getComment();
        $this->labeled = $item->isLabeled();
        $this->transfered = $item->isTransfered();
        $this->sold = $item->isSold();
        $this->state = $item->getState();
    }
}
