<?php
class View{
    private $arrCongig = array(
        'suffix' => '.html',        //模板后缀
        'templateDir' => '',        //模板文件夹
        'compileDir' => '',         //缓存和编译后存储的内容
        'cache_htm' => true,        //是否缓存
        'suffix_cache' => '.htm',   //缓存后缀
        'cache_time' => 7200        //缓存过期时间
    );
    public $file;
    private static $instance = null;
    private $value = array();
    private $compileTool;
    private function __construct(){
        $request = Request::instance();
        $templateDir = APP_PATH.$request->app."/view/".$request->controller."/";
        $compileDir  = ROOT_PATH."/runtime/cache/";
        $this->arrCongig["templateDir"] = $templateDir;
        $this->arrCongig["compileDir"]  = $compileDir;
    }
    public static function instance(){
        if(is_null(self::$instance)){
            self::$instance = new View();
        }
        return self::$instance;
    }
    public function setConfig($key,$value = null){
        if(is_array($key)){
            $this->arrCongig = $key+$this->arrCongig;
        }else{
            $this->arrCongig[$key] = $value;
        }
    }
    public function getConfig($key = null){
        if($key){
            return $this->arrCongig[$key];
        }else{
            return $this->arrCongig;
        }
    }
    public function assign($key,$value){
        $this->value[$key] = $value;
    }
    public function __set($name,$arg)
    {
        $this->value[$name] = $arg;
    }
    public function path(){
        return $this->arrCongig["templateDir"].
                    $this->file.$this->arrCongig["suffix"];
    }
    public function needCache(){
        return $this->arrCongig["cache_htm"];
    }
    public function reCache($file){
        $cacheFile = $this->arrCongig["compileDir"].md5($file).'.htm';
        $flag = true;
        if($this->needCache()){
            if(is_file($cacheFile)){
                $flag = (time()-@filemtime($cacheFile))<$this->arrCongig["cache_time"]?false:true;
            }else{
                $flag = true;
            }
        }
        return $flag;
    }
    public function show($file=""){
        if($file == ""){
            $this->file = Request::instance()->action;
        }else{
            $this->file = $file;
        }
        if(!is_file($this->path())){
            exit("找不到对应的模板");
        }
        //编译后的php文件
        $compileFile = $this->arrCongig["compileDir"].md5($file).'.php';
        //静态缓存文件
        $cacheFile = $this->arrCongig["compileDir"].md5($file).'.htm';
        //是否需要重新生成静态文件
        if($this->reCache($file)){
            if($this->needCache()){ob_start();}
            extract($this->value);
            if(!is_file($compileFile) || filemtime($compileFile)<filemtime($this->path())){
                if(!is_dir($this->arrCongig["compileDir"])){
                    mkdir($this->arrCongig["compileDir"]);
                }
                $this->compileTool = new CompileTool($this->path());
                //生成缓存文件
                $this->compileTool->compile($compileFile);
                include $compileFile;
            }else{
                include $compileFile;
            }
            if($this->needCache()){
                file_put_contents($cacheFile, ob_get_contents());
            }
        }else{
            readfile($cacheFile);
        }
    }
}
class CompileTool{
    private $template;              //待编译的文件
    private $content;               //需要替换的文本
    private $comfile;               //编译后的文件
    private $left = '{';            //左定界符
    private $right = '}';           //右定界符
    private $T_P = array();
    private $T_R = array();
    public function __construct($template){
        $this->content = file_get_contents($template);
        //匹配变量 如{$data}
        $this->T_P[] = "#\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}#";
        //匹配foreach 如{foreach $data as $val}
        $this->T_P[] = "#\{(foreach)\s+?\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+?(as)\s+?\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}#";
        //匹配循环体内的数组输出 如{$val['k']}
        $this->T_P[] = "#\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\[.*\])\}#";
        //匹配循环闭合标签 {/foreach}
        $this->T_P[] = "#\{(\/foreach)\}#";
        //匹配if标签
        $this->T_P[] = "#\{if(.*?)\}#";
        //匹配if闭合标签
        $this->T_P[] = "#\{(\/if)\}#";
        //匹配else if
        $this->T_P[] = "#\{(else if|elseif)(.*?)\}#";
        //匹配else
        $this->T_P[] = "#\{else\}#";

        /*替换变量 如替换{$data} 为?php echo $data;?>*/
        $this->T_R[] = "<?php echo \$\\1;?>";
        /*替换foreach 如替换{foreach $data as $val}
        为<?php foreach($data as $val){?>*/
        $this->T_R[] = "<?php foreach(\$\\2 as \$\\4){?>";
        //替换数组输出 如{$val['k']}替换为?php echo $val['k'];?
        $this->T_R[] = "<?php echo \$\\1\\2;?>";
        /*替换循环闭合标签{/foreach}为 <?php }?>*/
        $this->T_R[] = "<?php }?>";
        //替换if标签
        $this->T_R[] = "<?php if(\\1){?>";
        //替换if闭合标签
        $this->T_R[] = "<?php }?>";
        //替换else if标签
        $this->T_R[] = "<?php }else if(\\2){?>";
        //替换else标签
        $this->T_R[] = "<?php }else{?>";
    }
    public function compile($destFile){
        if(strpos($this->content, '{') != false){
            $this->content = preg_replace($this->T_P, 
                $this->T_R, $this->content);
        }
        file_put_contents($destFile, $this->content);
    }
}