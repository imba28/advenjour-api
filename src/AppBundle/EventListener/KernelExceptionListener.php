<?php
namespace AppBundle\EventListener;

use AppBundle\JsonAPI\Document;
use AppBundle\JsonAPI\ErrorObject;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class KernelExceptionListener
{
    /**
     * @var ApplicationLogger
     */
    private $logger;

    /**
     * KernelExceptionListener constructor.
     * @param ApplicationLogger $logger
     */
    public function __construct(ApplicationLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Listen for exceptions and return json error response.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        $document = new Document();
        $document->addError(new ErrorObject($exception));
        $response = new JsonResponse($document);

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->logger->error($exception->getMessage() . "\n\n". $exception->getTraceAsString() . "\n\n" . Tool::getClientIp(), [
            'component' => $exception->getTrace()[0]['class'],
        ]);

        $event->setResponse($response);
    }
}