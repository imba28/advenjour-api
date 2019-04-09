<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\EventCategorySerializer;
use Exception;
use Pimcore\Model\DataObject\EventCategory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=images)",
     *     type="array",
     *     @SWG\Items(type="string"),
     * ),
     * @SWG\Parameter(
     *     name="filter",
     *     in="query",
     *     description="Add filters. (filter[name]=foobar or filter[price]=<::5&filter[name]=like::york)",
     *     type="array",
     *     @SWG\Items(
     *      type="string"
     *     ),
     * ),
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Set result limit. Default value is 25."
     * ),
     * @SWG\Parameter(
     *     name="offset",
     *     in="query",
     *     type="integer",
     *     description="Set result offset. Use this in combination with limit to create a pagination."
     * )
     *
     * @SWG\Tag(name="EventCategory")
     * @SWG\Response(response=200, description="List of requested objects")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function listAction(Request $request)
    {
        $list = new EventCategory\Listing();
        if ($orderBy = $request->get('orderBy')) {
            $list->setOrderKey($this->escapePropertyString($orderBy));
        }
        if ($order = $request->get('order')) {
            $list->setOrder($this->escapePropertyString($order));
        }

        if ($parentCategory = $request->get('id')) {
            $list->setCondition('parentCategory__id = ?', [$parentCategory]);
        } else {
            $list->setCondition('parentCategory__id IS NULL');
        }

        if ($filter = $request->get('filter')) {
            $this->filterCollectionByRequest($list, $filter);
        }

        $limit = intval($request->get('limit', 25));
        $offset = intval($request->get('offset', 0));
        $this->selectCollectionBoundsByRequest(
            $list,
            $limit,
            $offset
        );

        return $this->success(
            $this->serializer->serializeResourceArray($list->load()),
            Response::HTTP_OK, [
                'limit' => $limit,
                'offset' => $offset,
                'countTotal' => $list->count(),
                'countResult' => count($list->load())
            ]
        );
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
     * ),
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=images)",
     *     type="array",
     *     @SWG\Items(type="string"),
     * ),
     * @SWG\Tag(name="EventCategory")
     * @SWG\Response(response=200, description="The requested objects")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function detailAction(Request $request)
    {
        if ($category = EventCategory::getById($request->get('id'))) {
            return $this->success($this->serializer->serializeResource($category));
        }

        throw new NotFoundHttpException('Item not found!');
    }
}