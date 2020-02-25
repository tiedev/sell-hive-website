<?php

namespace Entity;

/**
 * @OA\Schema(@OA\Xml(name="PdfFile"))
 */
class PdfFile
{
    /**
     * type of pdf file
     * @OA\Property(enum={"labels", "test", "list"})
     * @var string
     */
    public $type;

    /**
     * @OA\Property()
     * @var string
     */
    public $name;

    /**
     * @OA\Property()
     * @var string
     */
    public $url;
}
