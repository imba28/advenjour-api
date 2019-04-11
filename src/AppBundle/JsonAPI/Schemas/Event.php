<?php
namespace AppBundle\JsonAPI\Schemas;

use Swagger\Annotations as SWG;

class Event
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
     *     type="object",
     *     @SWG\Property(property="from", type="string", format="date-time"),
     *     @SWG\Property(property="to", type="string", format="date-time")
     * )
     */
    public $date;

    /**
     * @SWG\Property(
     *     type="object",
     *     @SWG\Property(property="value", type="number", description="Price value"),
     *     @SWG\Property(property="unit", type="string", description="Currency")
     * )
     */
    public $price;

    /**
     * @SWG\Property(
     *     type="integer",
     *     minimum="0",
     *     maximum="5"
     * )
     */
    public $rating;

}