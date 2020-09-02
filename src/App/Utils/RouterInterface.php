<?php
namespace App;

interface RouterInterface
{
    public function get($path, callable $handler);
    public function put($path, callable $handler);
    public function post($path, callable $handler);
    public function delete($path, callable $handler);
}

