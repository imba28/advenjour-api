<?php
namespace AppBundle\JsonAPI\Schemas;

use Swagger\Annotations as SWG;

class EventLocation
{
    /**
     * @SWG\Property(
     *     type="string"
     * )
     */
    public $name;

    /**
     * @SWG\Property(
     *     type="string"
     * )
     */
    public $address;

    /**
     * @SWG\Property(
     *     type="string"
     * )
     */
    public $city;

    /**
     * @SWG\Property(
     *     type="string"
     * )
     */
    public $zip;

    /**
     * @SWG\Property(
     *     type="string"
     * )
     */
    public $country;

    /**
     * @SWG\Property(
     *     type="object",
     *     @SWG\Property(property="lat", type="number"),
     *     @SWG\Property(property="long", type="number")
     * )
     */
    public $geo;
}
