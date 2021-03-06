# Rouge Framework

A Micro-Framework for fast creation of single page apps(SPA) or webs with PHP.

**Warning**: this is totally experimental for the moment, it was not tested in a real working environment, some syntax can change from a day to another and probably there is a lot of bugs, use it under your risk.

**I need help with development/documentation/testing/english so if someone is interested please send me a message.**

**[Support me on Patreon](https://www.patreon.com/marianofromlaruta)**

Features:

* Single page navigation: load every page of the same domain/url inside a content element with history support
* Twig templates support
* Server Side Rendering
* Very lightweight and fast for the client and server: the js script's weight is only 6kB (no gzipped) and the php 42kB
* Auto-render templates: you actually don't need to know how to program in php at all, you can add your twig templates inside "views" folder and it will render when the url match the name of the file(without the extension)
* Auto load controllers and templates: of course if you know php it will better, just add your controller in "controllers" folder and it will render the template with the same name of the controller automatically, if you prefer render manually another template you can do it too
* Custom routes: you can create custom routes programmatically for APIs similar to laravel or express
* Render to file
* Error handler
* FREE(MIT license)
* Reactivity
* Shared variables between PHP and JS

Future Features:

* Angular/VueJS/React reactivity styles
* GraphQL similar system for consult/manipulate data
* Multiple template engines support
* PHP 5 support(sorry, is only compatible with php 7 for now)
* Content preloader and lazy load
* Custom UI controls
* Full site compiler to HTML/JS/CSS
* Session manager
* Multi-elements renderer
* Rust flavour
* A free CMS based on the library

Why use Rouge and not another JS/PHP framework?

* Easy to learn: there's nothing complicated, if you already know how to use twig and php you probably only need another 15 minutes to learn the rest.
* Fast deploy: just upload your site to your server, run the composer installer and it is ready to use with full SSR out of the box.
* Is fast: actually is very fast in the server and client with very low consumptions of resources in both sides.
* Is cheap: the framework is free and the servers where you can run it are cheap and sometimes are free, the only requirement is have php7 and apache2/nginx installed.
* Is easy to use for designers: I already say it before but if you only know how to make web pages without the programmatic part is ok because is not needed, stop struggling trying to learn how to use some complicated Nodejs library for use the SPA feature, you only need to know where to put the files and Rouge will care of the rest.

## Guide

**Installation**

Run on your pc/server:

    git clone https://github.com/marianocarrazana/rouge.git
    composer install

For Nginx try to adding this to your configuration file:

    server {
        ...
         rewrite ^(?!public).* app.php last;
        ...
    }

**Set the configuration**

Open the config.json file and adapt the content for your site, for now only change the `site_url` to your actual site URL.

`paths.views`: is the folder where you put your HTML templates.

`paths.controllers`: is the folder where you put your php logic.

`paths.sections`: is the folder where are templates that can be loaded from others templates.

`default_title`: the title that will be displayed if there is not a title defined. You can define a title inside your controller with `$store->addServerVariable("title","my title")`.

`site_url`: the full site url to add to the relative href and src paths, this is necessary for the SPA feature, if your site domain is something like "mysite.com" put in the configuration `//mysite.com` or if you want to force the use of https `https://mysite.com`, if the web/app is inside a subfolder add the full path `//mysite.com/apps/rouge_app`.

`eval_scripts`: (true or false) scripts shared with `$reactor->addScript()` will be evaluated with eval() function, this is unsafe if your using a non https site.

`root_dir`: the full path of your project in your file system, this will used to solve the all paths defined in the paths.php/templates/sections.

`templates_extension`: the file extension of your templates, generally "html" or "twig".

`router.enable`: (true or false) it will auto-load the Rouge\Router class.

`router.auto_routes`: (true or false) auto-generate routes.

`router.global_variable`: (default: router) variable global name.

`router.auto_render`: (true or false) automatically render templates at the end of the controller.

`renderer.enable`: (true or false) it will auto-load the Rouge\Renderer class.

`renderer.global_variable`: (default: renderer) variable global name.

`renderer.content_element_id`: (default: content) unique HTML element id to be replace with actual content.

`error_handler.enable`: (true or false) it will auto-load the Rouge\ErrorHandler class.

`error_handler.debug_mode`: (true or false) show extra information like templates or controllers missing.

`error_handler.display_on`: (`all`,`none`,`html`,`console`) print errors on navigator console, html content, both or disable completely.

`error_handler.logger_global_variable`: (default: console) variable global name for the logger.

**Create a simple page**

If you want to add a page accessible in with the url `mysite.com/mypage` just add a file with the name `mypage.html` inside the `src/views` folder, remember you don't need to define the headers or sections that are shared for all pages inside this document, if you want to change the default design edit the `src/sections/base.html` file just remember to leave the `{% block content %}{% endblock %}` inside `#content` element for the SPA featured. For a root urls like `mysite.com/` edit the `index.html` inside the `src/views` folder.

Inside the mypage.html put this:

    Hello world! My name is {{name}}

This will render in "Hello world! My name is" without the  {{name}} part, that is because there is not variable "name" defined. Don't worry we will add one in the next point. If you don't know what is this try to read the twig documentation:

[https://twig.symfony.com/doc/2.x/templates.html](https://twig.symfony.com/doc/2.x/templates.html)

**Add a controller for your page**

Create a `mypage.php` inside the `src/controllers` folder with this content:

```php
<?php
global $store;
$store->addVariable("name","Maria");
```


Reload `mysite.com/mypage` and we will see "Hello world! My name is Maria".

`$store` is a instance of `Rouge\Store` class generated automatically and saved like a global variable.

You can access to parameters from the URL with the `$params` array.

**Add a link in your navigation menu with SPA support**

This is very simple, just add an `A` element with a relative path inside the route attribute, something like `<a route="mypage">My Page</a>`. Try adding this to the `views/sections/navigation.html` file inside the links list:

```html
<ul ..>
    <li><a route=".">Home</a></li>
    <li><a route="mypage">My Page</a></li>
    ...
</ul>
```

Reload the page and try to click in all the links and you will see how the content is loaded without full reloading the page.

## Router

The router makes create routes very easily, the auto-generator is enabled by default and is not necessary that you add routes manually, but there is some extras features that probably you will need on more complex sites design.

The router variable is accessible in global context with `$router`.

`$router->addRoute('url','function or controller/template name')`: manually create a route.

`$router->checkRoutes()`: this is called generally when all your data/model is loaded to check existing routes and load/render the controllers/templates.

This is just a example, the URL and the variables support regular expressions.

    $router->addRoute("say/{something}", function ($params) use($renderer) {
        $renderer->renderString("{{something}}", $params);
    }, ["GET", "POST"]); //this will render on "say/hello" URL the text "hello"

`$renderer` is a global variable created(automatically, like $router) with the instance of the class Rouge\Loader

`{something}` is a parameter name, you  can get the content with `$params['something']`

A route more complex can be `"(sum|add)/{num1:number}/{num2:\d+}"` where `(sum|add)` are regexp,sum or add word, and `num1` and `num2` variables are numbers(`number` can be a regular expression too like `\d+`).

## Store

The store is used to share variables between the server and client.

In php(server side) you can define globals variables with `$store->setVariable('varName','value')` this variable will be accesible with the the template engine(server side) using `{{varName}}` and from javascript with `Store.varName` or `window.Store.varName`.

If you need to define a server only variable you can use `$store->setServerVariable('varName','value')` this will be accesible only for the template engine.

If you need to define a client only variable you can use `$store->setClientVariable('varName','value')` this will be accesible only for javascript.

`$store` is a global variable defined by the loader class.

## Reactor

The reactor adds reactivity and evaluate(or execute) all scripts on client side. Also parse html content with javascript content.

On server side you can use `$reactor->addScript('-js script-')` to add a custom js scripts to execute on client side.

## Reactivity

The Rouge reactivity is inspired on VueJS and Svelte, it uses getters and setters to update the content and all of them are precomputed on server side with no virtual-dom. 

To learn how to add reactivity to your web/app you only need 3 minutes, is really easy.

First we need to define a variable in our store in the server side. We can use `$store->addClientVariable('varName','value')` or `$store->addVariable('varName','value')`(this will be available on server side to use with twig too). 

Now we only need to define the `varName` on the client side too. There is two methods for this:

1. Use HTML attributes with `Store.varName`.
2. Use the `<reactor></reactor>` tag.

To understand better we can test a real example:

reactivity.php

```php
<?php
global $store;
$store->addVariable("name","");
```

reactivity.html
```html
<p content='Store.name'></p>
```

With this we are telling to render Store.name variable inside our `p` tag. The `content` attribute is parsed on server side and tell to the client that the content is the window.Store.name javascript variable. Now we can open the console and change that value, we can use `Store.name = 'Maria'` press enter and the `p` content will be changed to `Maria`. We can use HTML tags or attributes to define a new `p` content, for example `Store.name = '<b style="color:red">Maria</b>'`;

We are using pure javascript, nothing new, so if we want to add an input that changes the `Store.name` variable we can use something like this:

reactivity.html
```html
<p content='Store.name'></p>
<input type="text" value="" onkeyup="Store.name = this.value" />
```

So with `onkeyup="Store.name = this.value"` we are telling to client that changes `Store.name` value when a key is released.

But if we want to process data before print our name we need use the `reactor` tag. The reactor tag is the same that `script` tag with 2 differences: in the client side is render like an HTML element(by default a `div`) and inside our script we have a `me` variable, this is used like reference to our html element, so to change the content we use pure javascript:

reactivity.html
```html
<reactor>
me.innerHTML = "My name is " + Store.name;
</reactor>
<input type="text" value="" onkeyup="Store.name = this.value" />
```

With this we are telling to change the innerHTML from our element to "My name is " plus the content from `Store.name`, the reactivity will always work inside the reactor tag but it will not work outside of this.

And that's all for now, the final version will be available on composer until then I don't recommend to use this in a professional project.
