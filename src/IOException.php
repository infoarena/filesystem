<?php

namespace Infoarena\Filesystem;

class IOException extends \Exception
{
    protected $message;

    public function __construct($message, $filepath)
    {
        $this->message = $message;
        $this->filepath = $filepath;
    }

    public function getFilepath()
    {
        return $this->filepath;
    }
}
