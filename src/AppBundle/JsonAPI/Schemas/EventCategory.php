<?php
namespace AppBundle\JsonAPI\Schemas;

use Swagger\Annotations as SWG;

class EventCategory
{
    /**
     * @SWG\Property(
     *     type="string"
     * )
     */
    public $name;

    /**
     * @var Asset
     */
    public $image;

    /**
     * @todo make this work
     * @var string
     */
    public $parentCategory;
}