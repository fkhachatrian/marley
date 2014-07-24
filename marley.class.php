<?php

//
// Minimalistic PHP library for Sinatra-like and Rails-like app development.
// 
// Version: 0.1
// Author: Elva
// Website: marley.elva.org
// License: MIT
//

require_once 'marley_route.trait.php';

class Marley {

    use MarleyRoute;


    //
    // Static Properties
    //
    private static $root_dir;
    private static $controllers_dir = '/controllers';
    private static $views_dir       = '/views';
    private static $layouts_dir     = '/views/layouts';

    //
    // Instance Properties
    //
    private $view_context     = null;
    private $view_is_rendered = false;



    // 
    // Set root directory path.
    //
    public function set_root_dir($absolute_path) {
        self::$root_dir = $absolute_path;
    }

    //
    // Get the base url of the website including subdomain and port number.
    //
    public function base_url() {
        $protocol = strpos(getenv('SERVER_PROTOCOL'), 'HTTPS') !== false ? 'https' : 'http';
        return $protocol . '://' . getenv('HTTP_HOST');
    }

    //
    // Serve 'GET' routes.
    //
    public function get($route, $callback) {
        self::serve('get', $route, $callback);
    }

    //
    // Serve 'POST' routes.
    public function post($route, $callback) {
        self::serve('post', $route, $callback);
    }

    //
    // Render a view.
    //
    public function render($view_name, $options = ['layout' => 'main'], $data = null) {
        extract($data ?: get_object_vars($this->view_context));

        $layout_path = self::$root_dir . self::$layouts_dir . '/' . $options['layout'] . '.html.php';
        $view_path   = self::$root_dir . self::$views_dir . '/' . $view_name . '.html.php';

        if($options['layout']) {
            if(!file_exists($layout_path)) {
                exit('Layout not found.');
            } else {
                ob_start();
                include_once $layout_path;
                $layout = ob_get_clean();
            }
        }        

        if(!file_exists($view_path)) {
            exit('View not found.');
        } else {
            ob_start();
            include_once $view_path;
            $view = ob_get_clean();
        }

        print $layout ? str_replace('{{yield}}', $view, $layout) : $view;

        $this->view_is_rendered = true;
        $this->view_context = null;
    }

}