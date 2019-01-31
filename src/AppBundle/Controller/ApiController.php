<?php
namespace AppBundle\Controller;

use AppBundle\JsonAPI\Document;
use AppBundle\JsonAPI\ErrorObject;
use AppBundle\JsonAPI\ResourceIdentifier;
use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * @todo Das geht irgendwie auch schöner...
     * @Route("/{path}", methods={"OPTIONS"}, requirements={"path"=".+"})
     */
    public function optionsCorsAcceptAction()
    {
        return new Response(null, 200);
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
}
