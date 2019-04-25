<?php
namespace AppBundle\JsonAPI;

use Pimcore;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ErrorObject implements \JsonSerializable
{
    private $exception;

    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function jsonSerialize()
    {
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $title = 'An error occurred.';

        if ($this->exception instanceof HttpExceptionInterface) {
            $status = $this->exception->getStatusCode();

            if (Pimcore::inDebugMode() || $this->exception instanceof HttpExceptionInterface) {
                $title = $this->exception->getMessage();
            }
        } else if ($this->exception instanceof AuthenticationException) {
            $status = 403;
            $title = $this->exception->getMessage();
        }

        return [
            'status' => $status,
            'title' => $title,
            'info' => Pimcore::inDebugMode() ? $this->exception->getTraceAsString() : null
        ];
    }
}