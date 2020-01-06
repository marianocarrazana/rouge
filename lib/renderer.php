<?php
namespace Rouge;

/**
 * Renderer
 */
class Renderer
{

    private $app               = null;
    private $rendered              = false;
    private $paths                 = [];
    private static $twigExtensions = [];
    private $twig                  = null;
    private $sectionsFiles         = [];
    private $config                = [];

    public function __construct(Loader &$app)
    {
        $this->app = $app;
        $this->paths   = $app->getConfig("paths");
        $sections      = scandir($this->paths["sections"]);
        foreach ($sections as $i) {
            if (preg_match('/[^\.]/', $i)) {
                if (Loader::isWindows()) {
                    $this->sectionsFiles[$i] = file_get_contents("{$this->paths['sections']}\\" . $i);
                } else {
                    $this->sectionsFiles[$i] = file_get_contents("/{$this->paths['sections']}/" . $i);
                }

            }
        }
        $this->config = $app->getConfig("renderer");
    }

    public function renderFile(string $filePath){
        $path = $this->paths["views"] . $filePath;
            if (!file_exists($path)) {
                trigger_error("Template not found: {$path}", E_USER_ERROR);
            }
        $template = file_get_contents($path);
        return $this->render($template);
    }

    public function render(string $template = "", $serverVariables=[]) {
        $errors        = "";
        $log           = "";
        $error_handler = $this->app->getConfig("error_handler");
        $store = $this->app->getStore();

        // Reactor
        $reactor = $this->app->getReactor();
        $pageId = $this->app->getUrl();
        $clientVariables = $store->getClientVariables();
        $template = $reactor->parseStoreAttr($template,$pageId,$clientVariables);
        $template = $reactor->parseReactorTag($template,$pageId);
        $template = $reactor->loadScripts($template);
        $reactor->generateStore($clientVariables);
        $reactor->generateFunctions();
        $scripts = $reactor->getScripts();

        $variables = array_merge($store->getServerVariables(),$serverVariables);

        if ($error_handler["debug_mode"]) {
            $logger = $this->app->getLogger();
            $errors = join("<br>", $logger->getHtmlErrors());
            $log    = join(";", $logger->getConsoleLog());
        }
        if (!isset($variables['title'])) {
            $variables['title'] = $this->app->getConfig("default_title");
        }
        if ($this->app->getFullMode()) {
            //include headers only on full request
            $onepagejs    = file_get_contents(__dir__ . "/onepage.js"); //include onepagejs in library mode too
            $eval_scripts = $this->app->getConfig('eval_scripts');
            $href = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $replaceState = "window.history.replaceState({'content':document.getElementById('content').innerHTML,'scripts':".json_encode(join(";", $scripts)).",'title':'{$variables['title']}'}, '', location.href);";
            $template = "{% extends 'base." . $this->app->getConfig('templates_extension') . "' %}{% block content %}{$errors}<script>{$log}</script>{$template}{% endblock %}";
            $extension = $this->app->getConfig('templates_extension');
            preg_match('/<head[^>]*>/', $this->sectionsFiles["base.{$extension}"],$headerTag);
            $headerScript = "{$headerTag[0]}<script type='text/javascript'>{$onepagejs}</script>";
            preg_match('/<\/body[^>]*>/', $this->sectionsFiles["base.{$extension}"],$bodyTag);
            $bodyScript = "<script type='text/javascript'>Rouge.site_url='{{site_url}}';Rouge.updateRoutes();Rouge.eval_scripts={$eval_scripts};\n" . join(";", $scripts) . ";\n{$replaceState}</script>{$bodyTag[0]}";
            $this->sectionsFiles["base.{$extension}"] = str_replace([$headerTag[0],$bodyTag[0]], [$headerScript,$bodyScript], $this->sectionsFiles["base.{$extension}"]);
        }
        $site_url                   = $this->app->getConfig('site_url');
        $variables['site_url']      = $site_url;
        $templateName = "ophp_string_renderer.html";
        $this->sectionsFiles[$templateName] = $template;
        foreach ($this->sectionsFiles as $key => $value) {
            //convert relative links url in absolute
            $patters = [
                '/(href|src)=[\'\"](?!#|(https?:)|(\/)|(\.\.)|({{))([^\'\"]+)[\'\"]/i',
                '/route=[\"\']([^\"\']*)[\"\']/i',
            ]; //relative routes
            $replace = [
                '\1="' . $site_url . '\6"',
                'data-route="' . $site_url . '\1" href="' . $site_url . '\1"',
            ]; //absolute routes
            $this->sectionsFiles[$key] = preg_replace($patters, $replace, $value);
        }

        $loader     = new \Twig\Loader\ArrayLoader($this->sectionsFiles);
        $this->twig = new \Twig\Environment($loader);

        $this->twig->addRuntimeLoader(new class implements \Twig\RuntimeLoader\RuntimeLoaderInterface
        {
            public function load($class)
            {
                foreach (Renderer::getTwigExtensions() as $key => $extension) {
                    if ($extension['class'] === $class) {
                        return $extension['callback']();
                    }
                }
            }
        });
        foreach (Renderer::$twigExtensions as $extension) {
            $this->twig->addExtension($extension['extension']);
        }

        $output = $this->twig->render($templateName, $variables);
        if (!$this->app->getFullMode()) {
            return $this->renderJSON([
                "title"   => $variables["title"],
                "content" => $output,
                'scripts' => join(";", $scripts),
                "errors"  => $errors,
                "console" => $log,
            ]);
        }
        $this->rendered = true;
        return $output;

    }

    public function renderPlainText(string $text = ""){
      header('Content-Type: text/plain');
      $this->rendered = true;
      return $text;
    }

    public function renderJSON($object = []){
      header('Content-Type: application/json');
      if(is_array($object))$object = json_encode($object);
      $this->rendered = true;
      return $object;
    }

    /**
     * Render a template loaded with the router
     * 
     * @param  string $path Template path
     * @return bool Return false if is already rendered with another method
     */
    public function autoRender(string $path)
    {
        if ($this->rendered) {
            return false;
        }
        echo $this->renderFile($path);
    }

    /**
     * Render a template file
     * @param  string $templatePath Template file path
     * @param  string $output Output file path
     * @return void
     */
    public function renderToFile(string $templatePath, string $output)
    {
        $content = $this->renderFile($name, $is_string);
        file_put_contents($output, $content);
    }

    public function addTwigExtension($class, callable $callback, $extension)
    {
        Renderer::$twigExtensions[] = ["class" => $class, "callback" => $callback, "extension" => $extension];
    }

    public static function getTwigExtensions()
    {
        return Renderer::$twigExtensions;
    }

    public function getRendered(){return $this->rendered;}

}
