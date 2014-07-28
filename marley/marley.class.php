<?php

//
// Marley 0.2
// 
// (c) Lasha Tavartkiladze and Elva.
// Distributed under the MIT license.
//
// Docs and examples: 
// http://marley.elva.org
//


require_once 'marley_url.class.php';
require_once 'marley_action.class.php';
require_once 'marley_template.class.php';
require_once 'marley_context.class.php';


class Marley {

    //
    // Global default options.
    //
    private $global_options = [
        'root_dir'       => '',
        'template_dir'   => '/views',
        'controller_dir' => '/controllers'
    ];


    //
    // Instance of the MarleyUrl class.
    //
    private $url;
 

    //
    // Create an Marley object and set default global options.
    //
    public function __construct($global_options = []) {
        $this->url = new MarleyUrl($_SERVER['REQUEST_URI']);

        $this->global_options['root_dir'] = $_SERVER['DOCUMENT_ROOT'];
        $this->global_options = array_merge($this->global_options, $global_options);
    }


    //
    // Shortcut wrapper for GET routes.
    //
    public function get($route, $callback, $route_options = []) {
        return $this->map('GET', $route, $callback, $route_options);
    }


    //
    // Shortcut wrapper for POST routes.
    //
    public function post($route, $callback, $route_options = []) {
        return $this->map('POST', $route, $callback, $route_options);
    }


    // 
    // Map current HTTP method and url to the passed route.
    //
    private function map($method, $route, $callback, $route_options) {
        if ($_SERVER['REQUEST_METHOD'] === $method && $match = $this->url->match($route)) {
            $this->run($callback, $match['params'], $route_options);
            exit; // Exit execution after the first match.
        }
    }


    //
    // Run a callback of the matched route.
    //
    private function run($callback, $route_params, $route_options) {
        $options = array_merge($this->global_options, $route_options);

        $context = new MarleyContext($options);
        $action  = new MarleyAction($callback, $options);

        // Change template directory to a folder with the same name as controller
        // if the action is a controller method.
        if ($action->is_controller) {
            $context->set_option('template_dir', $options['template_dir'] . '/' . $action->controller_name);
        }

        // Call the callback function, pass route paremeters 
        // and bind it to the created context object.
        $action->run($route_params, $context);

        // Automatically render template if the action is a controller method
        // and if template was not already rendered after $action->run() was called.
        $template_name = $action->action_name;
        if ($action->is_controller && !$context->is_rendered($template_name)) {
            $context->render($template_name);
        }
    }

}