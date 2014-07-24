<?php

trait MarleyRoute {

    //
    // Regex patterns for extracting route parameters.
    //
    private $patterns = [
        // Matches colon `:` then any character except slash `/`
        // /people/list/:name             => :name
        // /people/list/:age([0-9]{3})    => :age([0-9]{3})
        'whole_param' => '/:[^\/]+/',

        // Matches left parenthesis `(` then any character except slash `/` and then right parenthesis `)`
        // :name([a-z]+)                  => ([a-z]+)
        // :age([0-9]{3})                 => ([0-9]{3})
        'user_regex' => '/\([^\/]+\)/',

        // Matches column `:` then any character except left parenthesis `(` and slash `/`
        // :name([a-z]+)                  => :name
        // :age([0-9]{3})                 => :age
        'param_name' => '/:[^(\/]+/'
    ];

    //
    // If param contains a manual regex pattern from the user we return that, otherwise
    // we return a default pattern which matches "whole word" plus dash.
    //
    private function get_param_regex_pattern($param) {
        if(preg_match($this->patterns['user_regex'], $param, $matches)) {
            return $matches[0];
        } else {
            return '([-\w]+)';
        }
    }

    //
    // Turns route string into a regex that will be used to match the requested url.
    //
    // Examples:
    //
    // input   => /music/artists/:name
    // output  => /^\/music\/artists\/([-\w]+)\/?/
    //
    // input   => /music/artists/:track_id([0-9]+)
    // output  => /^\/music\/artists\/([0-9]+)\/?/
    //
    // input   => /music/artists/:name/:track_id([0-9]+)
    // output  => /^\/music\/artists\/([-\w]+)\/([0-9]+)\/?/
    //
    private function regexify($route) {
        // Replace each param (url parts that have a colun `:` inside) with corresponding regex pattern.
        $route_with_patterns = preg_replace_callback($this->patterns['whole_param'], function($matches) {
            return $this->get_param_regex_pattern($matches[0]);
        }, $route);

        // Escape slashes.
        $route_with_patterns = str_replace('/', '\/', $route_with_patterns);

        // Match from the start with optional slash in the end.
        return '/^' . $route_with_patterns . '\/?$/';
    }

    // 
    // Extracts parameter names as array from a route string.
    //
    // Examples:
    //
    // input   => /music/artists/:name
    // output  => [':name']
    //
    // input   => /music/artists/:track_id([0-9]+)
    // output  => [':track_id']
    //
    // input   => /music/artists/:name/:track_id([0-9]+)
    // output  => [':name', ':track_id']
    //
    private function get_route_param_names($route) {
        if(preg_match_all($this->patterns['param_name'], $route, $matches)) {
            return $matches[0];
        }
    }

    // 
    // Creates an associative array with param_name/param_value pairs.
    //
    // Examples:
    //
    // inputs  => [':name'], ['Queen']
    // output  => [':name' => 'Queen']
    //
    // inputs  => [':name', ':track_id'], ['Queen', 27]
    // output  => [':name' => 'Queen', ':track_id' => 27]
    //
    private function get_route_params($param_names, $param_values) {
        $params = [];

        if(is_array($param_names) && is_array($param_values)) {
            foreach($param_names as $index => $name) {
                $params[$name] = $param_values[$index];
            }
        }

        return $params;
    }

    //
    // match()
    //
    private function match($route) {
        $url = getenv('REQUEST_URI');

        $route_regex = $this->regexify($route);
        $param_names = $this->get_route_param_names($route);

        if(preg_match($route_regex, $url, $matches)) {
            return count($matches) > 1 ? $this->get_route_params($param_names, array_slice($matches, 1)) : true;
        } else {
            return false;
        }
    }

    //
    // serve()
    //
    private function serve($method, $route, $callback, $options = ['layout' => 'main']) {
        $http_method = strtolower(getenv('REQUEST_METHOD'));

        if($http_method === $method && $params = $this->match($route)) {
            if(is_array($params)) {
                $_GET = array_merge($_GET, $params);
            }

            if(is_string($callback)) {
                $parts = explode('#', $callback);
                $controller_name = $parts[0];
                $action_name = $parts[1];
                $this->serve_controller($controller_name, $action_name, $options, $params);

            } else if(is_callable($callback)) {
                $obj = new stdClass();
                $obj->marley = $this;
                $this->view_context = $obj;
                $cb = $callback->bindTo($obj);

                if(is_array($params)) {
                    call_user_func_array($cb, $params);
                } else {
                    call_user_func($cb);
                }
            }

            exit;
        }
    }

    //
    // serve_controller()
    //
    private function serve_controller($controller_name, $action_name, $options, $params) {
        $controller_path  = self::$root_dir . self::$controllers_dir . '/' . $controller_name . '_controller.php';
        $controller_class = ucfirst($controller_name) . 'Controller';
        
        require_once $controller_path;
        $obj = new $controller_class;
        $obj->marley = $this;
        $this->view_context = $obj;

        // Match method also with underscore (usefull to avoid PHP keywords error if you want to call method `new` for example)
        $method_name = method_exists($obj, $action_name) ? $action_name : '_' . $action_name;

        if(is_array($params)) {
            call_user_func_array(array($obj, $method_name), $params);
        } else {
            call_user_func(array($obj, $method_name));
        }

        // Auto render.
        if(!$this->view_is_rendered) {
            $view_name = $controller_name . '/' . $action_name;
            $this->render($view_name, $options);
        }
    }

}