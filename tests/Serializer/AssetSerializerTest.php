<?php

class AssetSerializerTest extends \Pimcore\Test\WebTestCase
{
    private $container;

    public function testFactory()
    {
        self::createClient();
        $this->container = self::bootKernel()->getContainer();

        /** @var \AppBundle\Serializer\SerializerFactory $factory */
        $factory = $this->container->get(\AppBundle\Serializer\SerializerFactory::class);
        $serializer = $factory->build(\Pimcore\Model\Asset::class);

        $this->assertTrue($serializer->supports(\Pimcore\Model\Asset::class));
    }
}