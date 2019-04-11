<?php
namespace AppBundle\Controller;

use AppBundle\JsonAPI\Document;
use AppBundle\JsonAPI\ErrorObject;
use AppBundle\JsonAPI\ResourceIdentifier;
use Pimcore\Controller\FrontendController;
use Pimcore\Model\Listing\AbstractListing;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiController extends FrontendController
{
    /**
     * Set locale based on http preferred language header.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $request->setLocale($request->getPreferredLanguage());

        $this->setViewAutoRender($request, false);
    }

    /**
     * @param array|ResourceIdentifier $data
     * @param int $httpStatus
     * @param array|null $metadata
     * @return JsonResponse
     */
    public function success($data, $httpStatus = 200, array $metadata = null)
    {
        $document = new Document($data);
        if ($metadata) {
            foreach ($metadata as $key => $value) {
                $document->addMetadata($key, $value);
            }
        }
        return $this->json($document, $httpStatus);
    }

    /**
     * @param string|array $errors
     * @param int $httpStatus
     * @return JsonResponse
     */
    public function error($errors, int $httpStatus = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        if (!is_array($errors)) {
            $errors = [$errors];
        }

        $document = new Document();

        foreach ($errors as $error) {
            $document->addError(new ErrorObject(new HttpException($httpStatus, $error)));
        }

        return $this->json($document, $httpStatus);
    }

    /**
     * Remove any non word, digit or underscore character from a string.
     *
     * @param string $string
     * @return string
     */
    protected function escapePropertyString(string $string): string
    {
        return preg_replace('/[^\w\_\d]+/', '', $string);
    }

    /**
     * Add condition to list object based on filter. Filter must be set in the query string.
     *
     * Examples:
     * filter[name]=foobar&filter[age]=<::5 => name = 'foobar' && age < 5
     * filter[name]=like::foobar => name LIKE '%foobar%'
     * filter[hidden]=<>::1 => hidden != 1
     *
     * @param AbstractListing $list
     * @param array $filters
     */
    protected function filterCollectionByRequest(AbstractListing $list, array $filters)
    {
        foreach ($filters as $property => $filter) {
            $column =  $this->escapePropertyString($property);

            if (preg_match('/^(?<type>[\w]+|\<|\>|\=|\<\>)\:\:/', $filter, $m)) {
                $filterValue = str_replace($m[0], '', $filter);

                switch ($m['type']) {
                    case 'like':
                        $list->addConditionParam("LOWER({$column}) LIKE ?", "%{$filterValue}%");
                        break;

                    case '=':
                        $list->addConditionParam("{$column} = ?", $filterValue);
                        break;

                    case '<':
                        $list->addConditionParam("{$column} < ?", $filterValue);
                        break;

                    case '>':
                        $list->addConditionParam("{$column} > ?", $filterValue);
                        break;

                    case '<>':
                        $list->addConditionParam("{$column} <> ?", $filterValue);
                        break;

                    case 'in':
                    case 'inDiff':
                        $filterValue = explode(',', $filterValue);

                        $condition = [];
                        $bindings = [];

                        $concatenator = $m['type'] === 'inDiff' ? 'AND' : 'OR';

                        foreach ($filterValue as $value) {
                            $condition[] = "FIND_IN_SET(?, {$column})";
                            $bindings[] = $value;
                        }

                        $list->addConditionParam(join(" {$concatenator} ", $condition), $bindings);
                        break;

                    case 'is':
                        $list->addConditionParam("{$column}__id = ?", $filterValue);
                        break;

                    default:
                        // todo handle error?
                }
            } else {
                $list->addConditionParam("{$column} = ?", $filter);
            }
        }
    }

    protected function selectCollectionBoundsByRequest(AbstractListing $list, $limit, $offset = 0) {
        $list->setLimit($limit);
        $list->setOffset($offset);
    }
}
