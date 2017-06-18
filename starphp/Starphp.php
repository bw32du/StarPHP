<?php
class Starphp{
    private $config;
    private $route;
    private $url;
    private $action;
    public function __construct($config,$route)
    {
        spl_autoload_register(array('Starphp','loadClass'));
        $this->config = $config;
        $this->route = $route;
        $this->url = $_SERVER['REQUEST_URI'];
        $this->action = $config["ROOT_ACTION"];
    }
    public function run()
    {
        $this->route();
    }
    //解析url
    public function route()
    {
        if($this->url != "/"){
           $this->initAction();
        }
        $controller = $this->action[1];
        $action = $this->action[2];
        Request::instance()->app = $this->action[0];
        Request::instance()->controller = $controller;
        Request::instance()->action = $action;
    
        $contro = new $controller;
        //操作和控制器同名，不调用。因为该控制器是构造函数
        if(strtolower($controller) != strtolower($action)) {
            call_user_func_array(array($contro,$action),array());
        }
    }
    public function initAction()
    {
        $url = trim($this->url, '/');
        $urlArr = explode("/",$url);
        $action = $urlArr[0];
        foreach ($this->route as $key => $value) {
            if($this->mateCheck($action,$key)) {
                $this->action = explode("/",$value);
                $this->initParam($key,$urlArr);
                break;
            }
        }
    }
    private function mateCheck($action,$route)
    {
        $len = strlen($action);
        if($action == substr($route,0,$len)) {
             return true;   
        }
        return false;
    }
    public function initParam($key,$urlArr)
    {
        $key = str_replace(":","",$key);
        $keyArr = explode("/",$key);
        if(count($keyArr) != count($urlArr)){
           return;
        }
        for($x=1; $x<count($keyArr); $x++){
            $_GET[$keyArr[$x]] = $urlArr[$x];
        }
    }
    // 自动加载控制器和模型类 
    public function loadClass($class)
    {
        $controller = APP_PATH .$this->action[0].'/controller/' . $class . '.php';
        $app_model = APP_PATH .$this->action[0].'/model/' . $class . '.php';
        $starphp_class = ROOT_PATH . 'starphp/' . $class . '.php';
        if (file_exists($controller)) {
            //加载应用控制器类
            include_once $controller;
        }elseif (file_exists($app_model)) {
            //加载应用模型类
            include_once $app_model;
        }elseif (file_exists($starphp_class)) {
            //加载框架类库
            include_once $starphp_class;
        }
    }
}
require APP_PATH."route.php";
require ROOT_PATH."config/config.php";
(new Starphp($config,$route))->run();