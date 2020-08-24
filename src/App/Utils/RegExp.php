<?php

namespace App\Utils;

class RegExp
{
    private $regex;
    private $flags;

    public function __construct($regex, $flags = null)
    {
        $this->regex = $regex;
        $this->flags = $flags;
    }

    public function exec($string)
    {
        $matches = [];
        preg_match($this, $string, $matches);
        return $matches;
    }

    public function test($string)
    {
        return preg_match($this, $string);
    }

    public function __toString()
    {
        return "/{$this->regex}/{$this->flags}";
    }
}
