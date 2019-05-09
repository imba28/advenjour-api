<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\SerializerFactory;
use Pimcore\Model\DataObject\EventLocation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * Class EventLocationController
 * @SWG\Tag(name="EventLocations")
 * @package AppBundle\Controller
 */
class EventLocationController extends ApiController
{
    /**
     * @var SerializerFactory
     */
    private $factory;

    /**
     * EventController constructor.
     * @param SerializerFactory $factory
     */
    public function __construct(SerializerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * List of event objects.
     *
     * @Route("/locations.json", methods={"GET"})
     *
     * @SWG\Parameter(
     *     name="orderBy",
     *     in="query",
     *     type="string",
     *     description="The field used to order events"
     * )
     * @SWG\Parameter(
     *     name="order",
     *     in="query",
     *     type="string",
     *     description="Sort results ascending or descending order. Possible values are DESC and ASC."
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
     * @SWG\Response(response=200, description="List of requested objects")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function listAction(Request $request)
    {
        $list = new EventLocation\Listing();

        if ($orderBy = $request->get('orderBy')) {
            $list->setOrderKey($this->escapePropertyString($orderBy));
        }
        if ($order = $request->get('order')) {
            $list->setOrder($this->escapePropertyString($order));
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

        $resource = $this->factory->build(EventLocation::class)->serializeResourceArray($list->load());

        return $this->success($resource, Response::HTTP_OK, [
            'limit' => $limit,
            'offset' => $offset,
            'countTotal' => $list->count(),
            'countResult' => count($list->load())
        ]);
    }

    /**
     * EventLocation single resource object.
     *
     * @Route("/location/{id}.json", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unqiue id of an existing event object."
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="The requested objects",
     *     @Model(type=AppBundle\JsonAPI\Schemas\EventLocation::class)
     * )
     * @SWG\Response(response=404, description="EventLocation not found.")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function detailAction(Request $request)
    {
        if ($event = EventLocation::getById($request->get('id'))) {
            return $this->success($this->factory->build(EventLocation::class)->serializeResource($event));
        }

        throw new NotFoundHttpException('Item not found!');
    }
}
