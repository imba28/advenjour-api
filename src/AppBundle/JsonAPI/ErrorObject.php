<?php
namespace AppBundle\JsonAPI;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorObject implements \JsonSerializable
{
    private $exception;

    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function jsonSerialize()
    {
        $status = $this->exception instanceof HttpExceptionInterface ? $this->exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $title = $this->exception->getMessage();

        return [
            'status' => $status,
            'title' => $title
        ];
    }
}