<?php
namespace AppBundle\JsonAPI\Schemas;

use Swagger\Annotations as SWG;

class Quest
{
    /**
     * @SWG\Property(
     *     type="string"
     * )
     */
    public $name;

    /**
     * @SWG\Property(
     *     type="string",
     * )
     */
    public $description;

    /**
     * @SWG\Property(
     *     type="boolean",
     * )
     */
    public $public;

    /**
     * @SWG\Property(
     *     type="array",
     *     @SWG\Items(type=Asset::class)
     * )
     */
    public $images;
}