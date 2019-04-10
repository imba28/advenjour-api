<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\SerializerFactory;
use Pimcore\Model\DataObject\Quest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class QuestController extends ApiController
{
    /**
     * @var SerializerFactory
     */
    private $factory;

    public function __construct(SerializerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Get list of quests.
     *
     * @Route("/quests.json", methods={"GET"})
     * @Route("/users/{id}/quests.json", methods={"GET"}, requirements={"id"="\d+"})
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
     * )
     * @SWG\Parameter(
     *     name="filter",
     *     in="query",
     *     description="Add filters. (filter[name]=foobar or filter[price]=<::5&filter[name]=like::york)",
     *     type="array",
     *     @SWG\Items(
     *      type="string"
     *     ),
     * )
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Set result limit. Default value is 25."
     * )
     * @SWG\Parameter(
     *     name="offset",
     *     in="query",
     *     type="integer",
     *     description="Set result offset. Use this in combination with limit to create a pagination."
     * )
     *
     * @SWG\Tag(name="Quest")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of quest objects."
     * )
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function listAction(Request $request)
    {
        $list = new Quest\Listing();

        if ($orderBy = $request->get('orderBy')) {
            $list->setOrderKey($this->escapePropertyString($orderBy));
        }
        if ($order = $request->get('order')) {
            $list->setOrder($this->escapePropertyString($order));
        }

        if ($id = $request->get('id')) {
            $list->addConditionParam('user__id = ?', $id);
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
            $this->factory->build(Quest::class)->serializeResourceArray($list->load()),
            Response::HTTP_OK, [
                'limit' => $limit,
                'offset' => $offset,
                'countTotal' => $list->count(),
                'countResult' => count($list->load())
            ]
        );
    }

    /**
     * Event single resource object.
     *
     * @Route("/quest/{id}.json", methods={"GET"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unqiue id of an existing quest object."
     * )
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=images)",
     *     type="array",
     *     @SWG\Items(type="string"),
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="The requested objects",
     *     @Model(type=AppBundle\JsonAPI\Schemas\Quest::class)
     * )
     * @SWG\Response(response=404, description="Quest not found.")
     *
     * @SWG\Tag(name="Quest")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function detailAction(Request $request)
    {
        if ($event = Quest::getById($request->get('id'))) {
            return $this->success($this->factory->build(Quest::class)->serializeResource($event));
        }

        throw new NotFoundHttpException('Item not found!');
    }
}