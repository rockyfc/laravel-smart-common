<?php

namespace Smart\Common\Comment;

class FieldObject extends \stdClass
{
    public $required = false;
    public $isRelation = false;
    public $type = 'String';
    public $default;
    public $comment;
    public $options = [];

    public function __construct(array $data = [])
    {
        $this->required = isset($data['required']) ? $data['required'] : false;
        $this->isRelation = isset($data['isRelation']) ? $data['isRelation'] : false;
        $this->type = isset($data['type']) ? $data['type'] : 'String';
        $this->default = isset($data['default']) ? $data['default'] : null;
        $this->comment = isset($data['comment']) ? $data['comment'] : null;
        $this->options = isset($data['options']) ? $data['options'] : [];
    }
}
