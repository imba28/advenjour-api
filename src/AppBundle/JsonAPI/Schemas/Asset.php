<?php
namespace AppBundle\JsonAPI\Schemas;

use Swagger\Annotations as SWG;

class Asset
{
    /**
     * @SWG\Property(
     *     type="string"
     * )
     */
    public $fullPath;

    /**
     * @SWG\Property(
     *     type="string|null",
     * )
     */
    public $title;

    /**
     * @SWG\Property(
     *     type="string|null",
     * )
     */
    public $description;

    /**
     * @SWG\Property(
     *     type="object",
     *     @SWG\Property(
     *         property="svgPlaceholder",
     *         type="string"
     *     )
     * )
     */
    public $thumbnails;
}