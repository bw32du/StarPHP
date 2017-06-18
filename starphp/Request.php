<?php
class Request
{
    public $app;
    public $controller;
    public $action;
    public $get;
    public $post;
    private static $request = null;
    private function __construct(){}
    public static function instance()
    {
        if(is_null(self::$request)){
           self::$request = new Request();
        }
        return self::$request;
    }
}