<?php
namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

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
    }
}