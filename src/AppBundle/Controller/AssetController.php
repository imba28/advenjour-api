<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\SerializerFactory;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

class AssetController extends ApiController
{
    private $factory;

    public function __construct(SerializerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Video single resource object.
     *
     * @Route("/public/asset/{id}.json", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unqiue id of an existing asset object."
     * ),
     * @SWG\Tag(name="Asset")
     * @SWG\Response(response=200, description="The requested objects")
     * @SWG\Response(response=404, description="Asset was not found")
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function videoAction(Request $request)
    {
        if ($asset = Asset::getById($request->get('id'))) {
            $serializer = $this->factory->build($asset);
            return $this->success($serializer->serializeResource($asset));
        }

        throw new NotFoundHttpException('Item not found!');
    }
}