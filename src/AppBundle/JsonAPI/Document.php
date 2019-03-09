<?php
namespace AppBundle\JsonAPI;

class Document implements \JsonSerializable
{
    /**
     * @var SingleResource|SingleResource[]
     */
    private $data = [];

    private $errors = null;

    private $metadata = [];

    public function __construct($data = null)
    {
        if (!is_array($data)) {
            $data = [$data];
        }
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
            $includes = [];
            $data = [];

            foreach ($this->data as $item) {
                $includes = array_merge($includes, $item->getIncludes());

                $data[] = array_filter(
                    array_merge(
                        [
                            'id' => $item->getIdentifier(),
                            'type' => $item->getType()
                        ],
                        array_filter([
                            'attributes' => $item->getAttributes(),
                            'relationships' => $item->getRelationships(),
                        ])
                    )
                );
            }



            return [
                'data' => count($data) === 1 ? $data[0] : $data,
                'meta' => $this->metadata,
                'included' => $includes
            ];
        } else {
            return [
                'errors' => $this->errors
            ];
        }
    }
}