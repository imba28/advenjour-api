<?php
namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
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
     * @param array $data
     * @param int $httpStatus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function success(array $data, $httpStatus = 200)
    {
        return $this->json($data, $httpStatus);
    }

    /**
     * @param string|array $errors
     * @param int $httpStatus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function error($errors, int $httpStatus = 400)
    {
        if (is_array($errors)) {
            $errors = [$errors];
        }

        return $this->json([
            'error' => $errors
        ], $httpStatus);
    }

    /**
     * @Route("/{path}", methods={"OPTIONS"}, requirements={"path"=".+"})
     */
    public function optionsCorsAcceptAction()
    {
        return new Response(null, 200);
    }
}