<?php

class AssetSerializerTest extends \Pimcore\Test\WebTestCase
{
    private $container;

    private $factory;

    /**
     * AssetSerializerTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->container = self::bootKernel()->getContainer();
        $this->factory = $this->container->get(\AppBundle\Serializer\SerializerFactory::class);
    }

    /**
     * @throws Exception
     */
    public function testSupports()
    {
        /** @var \AppBundle\Serializer\SerializerFactory $factory */
        $serializer = $this->factory->build(\Pimcore\Model\Asset::class);
        $this->assertTrue($serializer->supports(\Pimcore\Model\Asset::class));
    }
}