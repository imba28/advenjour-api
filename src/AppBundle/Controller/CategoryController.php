<?php
namespace AppBundle\Controller;

use AppBundle\JsonAPI\Document;
use AppBundle\Serializer\EventCategorySerializer;
use Pimcore\Model\DataObject\EventCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

class CategoryController extends ApiController
{
    private $serializer;

    public function __construct(EventCategorySerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * List of available base categories.
     *
     * @Route("/categories.json", methods={"GET"})
     * @Route("/category/{id}/categories.json", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @SWG\Parameter(
     *     name="orderBy",
     *     in="query",
     *     type="string",
     *     description="The field used to order categories. All returned resource parameters are valid (name, parentCategory, ...)."
     * )
     * @SWG\Parameter(
     *     name="order",
     *     in="query",
     *     type="string",
     *     description="Sort results ascending or descending order. Possible values are DESC and ASC"
     * )
     * @SWG\Tag(name="EventCategory")
     * @SWG\Response(response=200, description="List of requested objects")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function listAction(Request $request)
    {
        $list = new EventCategory\Listing();
        if ($orderBy = $request->get('orderBy')) {
            $list->setOrderKey($orderBy);
        }
        if ($order = $request->get('order')) {
            $list->setOrder($order);
        }

        if ($parentCategory = $request->get('id')) {
            $list->setCondition('parentCategory__id = ?', [$parentCategory]);
        } else {
            $list->setCondition('parentCategory__id IS NULL');
        }

        return $this->success($this->serializer->serializeResourceArray($list->load()));
    }

    /**
     * Category single resource object.
     *
     * @Route("/category/{id}.json", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unqiue id of an existing category object."
     * )
     * @SWG\Tag(name="EventCategory")
     * @SWG\Response(response=200, description="The requested objects")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function detailAction(Request $request)
    {
        if ($category = EventCategory::getById($request->get('id'))) {
            return $this->success($this->serializer->serializeResource($category));
        }

        throw new NotFoundHttpException('Item not found!');
    }
}