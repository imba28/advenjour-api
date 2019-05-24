<?php

class AssetSerializerTest extends \Pimcore\Test\WebTestCase
{
    /**
     * @var \AppBundle\Serializer\SerializerFactory
     */
    private $factory;

    protected function setUp() {
        $this->factory = self::bootKernel()->getContainer()->get(\AppBundle\Serializer\SerializerFactory::class);
    }

    public function testSupports()
    {
        /** @var \AppBundle\Serializer\SerializerFactory $factory */
        $serializer = $this->factory->build(\Pimcore\Model\Asset::class);
        $this->assertTrue($serializer->supports(\Pimcore\Model\Asset::class));
        $this->assertTrue($serializer->supports(\Pimcore\Model\DataObject\Data\Hotspotimage::class));
        $this->assertTrue($serializer->supports(\Pimcore\Model\Asset\Image::class));
        $this->assertTrue($serializer->supports(\Pimcore\Model\Asset\Video::class));
    }

    public function testSerialization()
    {
        $serializer = $this->factory->build(\Pimcore\Model\Asset::class);

        $image = $this->getAsset();
        $resource = $serializer->serializeResource($image);

        $this->assertEquals($resource->getIdentifier(), $image->getId());
        $this->assertEquals($resource->getType(), ucfirst($image->getType()));
        $this->assertEquals($resource->getAttribute('title'), 'Title');
        $this->assertEquals($resource->getAttribute('description'), 'Description');
    }

    public function testIdentifierSerialization()
    {
        $serializer = $this->factory->build(\Pimcore\Model\Asset::class);

        $image = $this->getAsset();
        $resource = $serializer->serializeResourceIdentifier($image);

        $this->assertEquals($resource->getIdentifier(), $image->getId());
        $this->assertEquals($resource->getType(), ucfirst($image->getType()));
    }

    public function testArraySerialization() {

        $assets = $this->getAssets();
        $serializer = $this->factory->build(\Pimcore\Model\Asset::class);

        $resources = $serializer->serializeResourceArray($assets);

        /** @var \AppBundle\JsonAPI\SingleResource $resource */
        foreach ($resources as $key => $resource) {
            $this->assertEquals($resource->getIdentifier(), $assets[$key]->getId());
            $this->assertEquals($resource->getType(), ucfirst($assets[$key]->getType()));
            $this->assertEquals($resource->getAttribute('title'), $assets[$key]->getMetadata('title'));
            $this->assertEquals($resource->getAttribute('description'), $assets[$key]->getMetadata('description'));
        }
    }

    public function testArrayIdentifierSerialization() {

        $assets = $this->getAssets();
        $serializer = $this->factory->build(\Pimcore\Model\Asset::class);

        $resources = $serializer->serializeResourceIdentifierArray($assets);

        /** @var \AppBundle\JsonAPI\SingleResource $resource */
        foreach ($resources as $key => $resource) {
            $this->assertEquals($resource->getIdentifier(), $assets[$key]->getId());
            $this->assertEquals($resource->getType(), ucfirst($assets[$key]->getType()));
        }
    }

    public function testSerializationOutput()
    {
        $asset = $this->getAsset();
        $serializer = $this->factory->build(\Pimcore\Model\Asset::class);

        $resource = $serializer->serializeResource($asset);
        $json = $resource->jsonSerialize();

        $this->assertArrayHasKey('id', $json, 'Resource should contain the key `id`');
        $this->assertArrayHasKey('type', $json, 'Resource should contain the key `type`');
        $this->assertArrayHasKey('attributes', $json, 'Resource should contain the key `attributes`');
        $this->assertArrayNotHasKey('meta', $json, 'Single resource should not contain the key `meta`');

        $this->assertEquals($asset->getId(), $json['id'], 'Resource include the asset id');
        $this->assertEquals(ucfirst($asset->getType()), $json['type'], 'Resource include the asset type');
        $this->assertEquals($asset->getMetadata('title'), $json['attributes']['title'], 'Resource include title meta data');
        $this->assertEquals($asset->getMetadata('description'), $json['attributes']['description'], 'Resource include description meta data');
        $this->assertEquals($asset->getFilename(), $json['attributes']['fullPath'], 'Resource include file path');
    }

    public function testSerializationThumbnails()
    {
        $asset = $this->getAsset();
        $serializer = $this->factory->build(\Pimcore\Model\Asset::class);
        $serializer->setThumbnails(['carouselImg', 'heroSlide']);

        $resource = $serializer->serializeResource($asset);
        $json = $resource->jsonSerialize();

        $this->assertArrayHasKey('thumbnails', $json['attributes'], 'Sesource should contain the key `thumbnails`');
        $this->assertCount(2, $json['attributes']['thumbnails']);
        $this->assertArrayHasKey('carouselImg', $json['attributes']['thumbnails']);
        $this->assertArrayHasKey('heroSlide', $json['attributes']['thumbnails']);
    }

    /**
     * @return \Pimcore\Model\Asset\Image
     */
    private function getAsset(): Pimcore\Model\Asset\Image
    {
        $image = new \Pimcore\Model\Asset\Image();
        $image->setFilename('test.png');
        $image->setParentId(0);
        $image->setId(42);
        $image->addMetadata('title','text', 'Title');
        $image->addMetadata('description', 'text', 'Description');

        return $image;
    }

    /**
     * @return \Pimcore\Model\Asset\Image[] array
     */
    private function getAssets(): array
    {
        $array = [];

        for ($i = 1; $i <= 3; $i++) {
            $image = new \Pimcore\Model\Asset\Image();
            $image->setFilename("test-{$i}.png");
            $image->setParentId(0);
            $image->setId($i);
            $image->addMetadata('title','text', 'Title');
            $image->addMetadata('description', 'text', 'Description');

            $array[] = $image;
        }

        return $array;
    }
}