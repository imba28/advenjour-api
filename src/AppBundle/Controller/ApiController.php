<?php

namespace AppBundle\Controller;

use AppBundle\JsonAPI\Document;
use AppBundle\JsonAPI\ErrorObject;
use AppBundle\JsonAPI\ResourceIdentifier;
use InvalidArgumentException;
use Pimcore\Controller\FrontendController;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Listing\AbstractListing;
use Pimcore\Tool;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

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
     * Decode request body and return associative array or std object
     * @param Request $request
     * @param bool $assoc
     * @return stdClass|array
     */
    protected function getRequestBodyJson(Request $request, bool $assoc = true)
    {
        if ($request->getMethod() === 'GET') {
            throw new InvalidArgumentException('Get request does not have a body!');
        }

        $data = json_decode($request->getContent(), $assoc);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException($this->get('translator')->trans('errors.json.invalid_body'));
        }

        return $data;
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
        $defaultLang = Tool::getDefaultLanguage();
        $tableName = "object_localized_{$list->getClassId()}_{$defaultLang}";
        $validColumnNames = $list->getValidTableColumns($tableName, true);

        $conditions = [];
        $conditionParams = [];

        foreach ($filters as $property => $filter) {
            $column = $this->escapePropertyString($property);

            if (!in_array($column, $validColumnNames)) {
                $logger = $this->get(ApplicationLogger::class);
                $logger->warning(Tool::getClientIp() . " tried to apply non valid filter '$column' to {$list->getClassName()} listing when accessing {$_SERVER['REQUEST_URI']}!");

                throw new BadRequestHttpException($this->get('translator')->trans('app.errors.invalid_filter', ['{column}' => $column]));
            }

            if (preg_match('/^(?<type>[\w]+|\<|\>|\=|\<\>|\<\=|\>\=)\:\:/', $filter, $m)) {
                $filterValue = str_replace($m[0], '', $filter);

                switch ($m['type']) {
                    case 'like':
                        $conditions[] = "LOWER({$column}) LIKE ?";
                        $conditionParams[] = "%{$filterValue}%";
                        break;

                    case 'is':
                    case '=':
                        $conditions[] = "{$column} = ?";
                        $conditionParams[] = $filterValue;
                        break;

                    case '<':
                        $conditions[] = "{$column} < ?";
                        $conditionParams[] = $filterValue;
                        break;

                    case '<=':
                        $conditions[] = "{$column} <= ?";
                        $conditionParams[] = $filterValue;
                        break;

                    case '>':
                        $conditions[] = "{$column} > ?";
                        $conditionParams[] = $filterValue;
                        break;

                    case '>=':
                        $conditions[] = "{$column} >= ?";
                        $conditionParams[] = $filterValue;
                        break;

                    case '<>':
                        $conditions[] = "{$column} <> ?";
                        $conditionParams[] = $filterValue;
                        break;

                    case 'in':
                    case 'inDiff':
                        $filterValue = explode(',', $filterValue);

                        $subCondition = [];
                        $bindings = [];

                        $concatenator = $m['type'] === 'inDiff' ? 'AND' : 'OR';

                        foreach ($filterValue as $value) {
                            $subCondition[] = "FIND_IN_SET(?, {$column})";
                            $bindings[] = $value;
                        }

                        $conditions[] = join(" {$concatenator} ", $subCondition);

                        foreach ($bindings as $binding) {
                            $conditionParams[] = $binding;
                        }
                        break;

                    default:
                        // todo handle error?
                }
            } else {
                $conditions[] = "{$column} = ?";
                $conditionParams[] = $filter;
            }
        }


        $list->setCondition(join(' AND ', $conditions), $conditionParams);

    }

    protected function selectCollectionBoundsByRequest(AbstractListing $list, $limit, $offset = 0)
    {
        $list->setLimit($limit);
        $list->setOffset($offset);
    }

    /**
     * Get folder by path. If folder or parent folder does not exist, the method will create it.
     * @param string $path
     * @param string $folderClass
     * @return Folder|\Pimcore\Model\Asset\Folder|\Pimcore\Model\Document\Folder
     */
    protected function getFolderRecursively(string $path, string $folderClass = Folder::class)
    {
        if ($path === '/') {
            return $folderClass::getByPath($path);
        }

        $parentFolders = explode('/', $path);

        $currentPath = '';
        $lastFolder = null;

        array_shift($parentFolders); // remove root path

        foreach ($parentFolders as $folderName) {
            if (strlen(trim($folderName)) === 0) {
                continue;
            }

            $currentPath .= "/$folderName";
            $folder = $folderClass::getByPath($currentPath);

            if ($folder === null) {
                $folder = new $folderClass();

                if ($folderClass === \Pimcore\Model\Asset\Folder::class) {
                    $folder->setFilename($folderName);
                } else {
                    $folder->setKey($folderName);
                }

                $folder->setParentId($lastFolder ? $lastFolder->getId() : 1);
                $folder->save();
            }

            $lastFolder = $folder;
        }

        return $lastFolder;
    }

    /**
     * @Route("/info", methods={"GET"})
     */
    public function infoAction()
    {
        return $this->json([
            'jsonapi' => [
                'version' => '1.0'
            ],
            'meta' => [
                'api' => [
                    'root' => Tool::getHostUrl() . '/api/v1',
                    'version' => 'v1'
                ]
            ]
        ]);
    }
}
