<?php
namespace AppBundle\JsonAPI;

class Document implements \JsonSerializable
{
    private $data;

    private $errors;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function addError($error) {
        $this->errors[] = $error;
    }

    public function jsonSerialize()
    {
        if ($this->data) {
            return [
                'data' => $this->data
            ];
        } else {
            return [
                'errors' => $this->errors
            ];
        }
    }
}