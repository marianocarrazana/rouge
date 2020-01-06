<?php
namespace Rouge;

require_once __dir__ . '/router.php';
require_once __dir__ . '/renderer.php';
require_once __dir__ . '/errorhandler.php';
require_once __dir__ . '/reactor.php';
require_once __dir__ . '/store.php';
require_once __dir__ . '/utils.php';

/**
 *
 */
class Loader
{

    private $fullMode       = true;
    private $config         = [];
    private $controllerPath = "";
    private $templateFile   = "";
    private $templateString = "";
    private $url            = "";
    private $router         = null;
    private $renderer       = null;
    private $store = null;
    private $reactor = null;
    private $errorHandler = null;
    private $logger = null;

    public function __construct(array $config)
    {
        $this->checkConfig($config);
        $this->config = $config;
        if($this->config["error_handler"]["enable"]){
            $this->errorHandler = new ErrorHandler($this);
            $this->logger = $this->errorHandler->getLogger();
            $GLOBALS[$config["error_handler"]["logger_global_variable"]] = $this->logger;
        }
        $headers = getallheaders();
        if (isset($headers["X-Rouge"])) {
            $x_onepagephp   = json_decode($headers["X-Rouge"], true);
            $this->fullMode = $x_onepagephp["fullMode"];
        } else {
            $this->fullMode = true;
        }
        if (preg_match('/[^\/]$/', $this->config['site_url'])) {
            $this->config['site_url'] .= '/';
        }
        if (!isset($_SERVER["REQUEST_URI"])) {
            trigger_error("No URL", E_USER_ERROR);
        }
        $relativePath = preg_replace('/\/\/[^\/]+(\/.*)/', '\1', $this->config['site_url']);
        $relativePath = "/^" . str_replace('/', '\/', $relativePath) . "?/";
        $requestURI   = preg_replace($relativePath, "", urldecode($_SERVER["REQUEST_URI"]));
        //no url,avoid open loader.php directly
        $paths     = explode("?", $requestURI); //separate url from parameters
        $this->url = $paths[0];
        if($this->url=="/")$path = "index";
        else{
            $paths     = explode("/", $paths[0]);
            foreach ($paths as $key => $value) {
                if ($value == '') {
                    $paths[$key] = "index";
                }
            }
            $path           = join("/", $paths);
        }
        foreach ($this->config["paths"] as $key => $value) {
            /* set absolute paths */
            $this->config["paths"][$key] = $this->config["root_dir"] . "/{$value}/";
        }
        if ($this->isWindows()) {
            foreach ($this->config["paths"] as $key => $value) {
                $this->config["paths"][$key] = preg_replace("/\//", "\\", $value);
            }
        }
        $this->controllerPath = $this->config["paths"]["controllers"] . "{$path}.php";
        $this->templateFile   = "{$path}.{$config['templates_extension']}";
        if ($this->config["router"]["enable"]) {
            $this->router      = new Router($this);
            $GLOBALS[$this->config["router"]["global_variable"]] = $this->router;
        }
        if ($this->config["renderer"]["enable"]) {   
            $this->renderer      = new Renderer($this);
            $GLOBALS[$this->config["renderer"]["global_variable"]] = $this->renderer;
        }
        if ($this->config["reactor"]["enable"]) {   
            $this->reactor      = new Reactor();
            $GLOBALS[$this->config["reactor"]["global_variable"]] = $this->reactor;
        }
        if ($this->config["store"]["enable"]) {   
            $this->store      = new Store();
            $GLOBALS[$this->config["store"]["global_variable"]] = $this->store;
        }
    }

    private function checkConfig(array &$config)
    {
        if(empty($config["root_dir"]))trigger_error("'root_dir' is needed",E_USER_ERROR);
        if(empty($config["site_url"]))trigger_error("'site_url' is needed",E_USER_ERROR);
        if(empty($config["paths"]["views"]))trigger_error("'paths.views' is needed",E_USER_ERROR);
        if(empty($config["paths"]["controllers"]))trigger_error("'paths.controllers' is needed",E_USER_ERROR);
        if(empty($config["paths"]["sections"]))trigger_error("'paths.sections' is needed",E_USER_ERROR);
        if(empty($config["default_title"]))$config["default_title"] = "Rouge";
        if(empty($config["eval_scripts"]))$config["eval_scripts"] = true;
        if(empty($config["templates_extension"]))$config["templates_extension"] = "html";
        if(empty($config["router"]["enable"]))$config["router"]["enable"] = true;
        if(empty($config["router"]["auto_routes"]))$config["router"]["auto_routes"] = true;
        if(empty($config["router"]["global_variable"]))$config["router"]["global_variable"] = "router";
        if(empty($config["router"]["auto_render"]))$config["router"]["auto_render"] = true;
        if(empty($config["renderer"]["enable"]))$config["renderer"]["enable"] = true;
        if(empty($config["renderer"]["content_element_id"]))$config["renderer"]["content_elemen_id"] = "content";
        if(empty($config["renderer"]["global_variable"]))$config["renderer"]["global_variable"] = "renderer";
        if(empty($config["error_handler"]["enable"]))$config["error_handler"]["enable"] = true;
        if(empty($config["error_handler"]["debug_mode"]))$config["error_handler"]["debug_mode"] = false;
        if(empty($config["error_handler"]["display_on"]))$config["error_handler"]["display_on"] = '';
        if(empty($config["error_handler"]["logger_global_variable"]))$config["error_handler"]["logger_global_variable"] = 'console';
        if(empty($config["store"]["enable"]))$config["store"]["enable"] = true;
        if(empty($config["store"]["global_variable"]))$config["store"]["global_variable"] = "store";
        if(empty($config["reactor"]["enable"]))$config["reactor"]["enable"] = true;
        if(empty($config["reactor"]["global_variable"]))$config["reactor"]["global_variable"] = "reactor";
    }

    public static function isWindows(){
        return '\\' === DIRECTORY_SEPARATOR;
    }

    public function getConfig(string $name)
    {
        if (!isset($this->config[$name])) {
            trigger_error("The index {$name} doesnt exist", E_USER_WARNING);
        } else {
            return $this->config[$name];
        }

    }
    public function getTemplatesPath()
    {return $this->config["paths"]["views"];}
    public function getTemplatesExtension()
    {return $this->config["templates_extension"];}
    public function getControllersPath()
    {return $this->config["paths"]["controllers"];}
    public function getControllerPath()
    {return $this->controllerPath;}
    public function getTemplate()
    {return $this->templateFile;}
    public function getUrl()
    {return $this->url;}
    public function getFullMode()
    {return $this->fullMode;}
    public function getRouter()
    {return $this->router;}
    public function getRenderer()
    {return $this->renderer;}
    public function getLogger()
    {return $this->logger;}
    public function getReactor()
    {return $this->reactor;}
    public function getStore()
    {return $this->store;}

}
