<?php

namespace Entity;

/**
 * @OA\Schema(@OA\Xml(name="SellerItemStatistic"))
 */
class SellerItemStatistic
{
    /**
     * @OA\Property()
     * @var int
     */
    public $all;

    /**
     * @OA\Property()
     * @var int
     */
    public $boxed_as_new;

    /**
     * @OA\Property()
     * @var int
     */
    public $labeled;

    /**
     * @OA\Property()
     * @var int
     */
    public $transfered;

    /**
     * @OA\Property()
     * @var int
     */
    public $sold;
}
