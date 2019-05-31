<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\SerializerFactory;
use Pimcore\Model\DataObject\Config;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class LandingpageController extends ApiController
{
    const CONFIG_OBJECT_PATH = '/config';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SerializerFactory
     */
    private $factory;

    public function __construct(SerializerFactory $factory)
    {
        $this->config = Config::getByPath(self::CONFIG_OBJECT_PATH);
        $this->factory = $factory;
    }

    /**
     * Get info about the active landing page.
     *
     * @Route("/public/landingpage/active.json", methods={"GET"})
     *
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=items)",
     *     type="array",
     *     @SWG\Items(type="string", enum={"items"}),
     * )
     *
     * @SWG\Tag(name="LandingPage")
     * @SWG\Response(response=200, description="Returns the current landing page object.")
     * @SWG\Response(response=404, description="No landing page is set.")
     */
    public function activeLandingPageAction()
    {
        $landingPage = $this->config->getActiveLandingpage();
        if ($landingPage === null) {
            throw new NotFoundHttpException($this->get('translator')->trans('app.errors.not_found'));
        }

        return $this->success($this->factory->build($landingPage)->serializeResource($landingPage));
    }
}