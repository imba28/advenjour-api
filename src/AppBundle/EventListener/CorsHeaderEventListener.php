<?php
namespace AppBundle\EventListener;


use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CorsHeaderEventListener
{
    /**
     * Listen for onKernelResponse event and append Cors header.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, PUT, POST, DELETE, HEAD, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-AUTH-TOKEN');

    }
}