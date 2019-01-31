<?php
namespace AppBundle\JsonAPI;

class Document implements \JsonSerializable
{
    private $data = [];

    private $errors = null;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function addError($error) {
        $this->errors[] = $error;
    }

    public function jsonSerialize()
    {
        if ($this->errors === null) {
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