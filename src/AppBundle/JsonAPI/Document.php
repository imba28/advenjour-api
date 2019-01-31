<?php
namespace AppBundle\JsonAPI;

class Document implements \JsonSerializable
{
    private $data = [];

    private $errors = null;

    private $metadata = [];

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function addMetadata($key, $value)
    {
        $this->metadata[$key] = $value;
    }

    public function addError($error) {
        $this->errors[] = $error;
    }

    public function jsonSerialize()
    {
        if ($this->errors === null) {
            return [
                'data' => $this->data,
                'meta' => $this->metadata
            ];
        } else {
            return [
                'errors' => $this->errors
            ];
        }
    }
}