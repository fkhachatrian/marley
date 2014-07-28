<?php

class MarleyAction {

    //
    // Array of controller specific options. 
    // These values can be changed from the constructor.
    //
    private $options = [
        'root_dir'                => '',
        'controller_dir'          => '/controllers',
        'controller_file_suffix'  => '_controller.php',
        'controller_class_suffix' => 'Controller'
    ];


    //
    // Reference to an internal callback closure object.
    //
    private $callback;


    //
    // Public properties that expose information about type of a callback we're dealing with.
    //
    public $is_controller;
    public $controller_name;
    public $action_name;


    //
    // Set default options and create an internal reference to the requested callback function. 
    //
    public function __construct($callback, $options) {
        $this->options['root_dir'] = $_SERVER['DOCUMENT_ROOT'];

        if (is_array($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->callback = is_string($callback) ? $this->controller_callback($callback) : $callback;

        if(!is_callable($this->callback)) {
            throw new InvalidArgumentException('Callback function isn\'t callable.');
        }
    }


    //
    // Call the callback function with optional parameters and a context object.
    //
    // $params is an array of paremeters whose elements are passed individually to the callback function.
    // $context is an object which becomes $this inside the callback function.
    //
    public function run($params = null, $context = null) {
        $f = $this->callback;

        if ($context) {
            $f = $f->bindTo($context);
        }

        if($params) {
            call_user_func_array($f, $params);
        } else {
            $f();
        }
    }


    //
    // Extract a requested function from a controller class.
    //
    // The passed $callback string has this format: `controller#action`, 
    // where `controller` is a name (without suffix) of a controller class
    // and `action` is a name of a method inside that controller class.
    //
    private function controller_callback($callback) {
        $o = $this->options;

        // Extract controller name and action name from the recieved callback string.
        $this->is_controller   = true;
        $controller_parts      = explode('#', $callback);
        $this->controller_name = $controller_parts[0];
        $this->action_name     = $controller_parts[1];

        // Include controller class file.
        $path = $o['root_dir'] . $o['controller_dir'] . '/' . $this->controller_name . $o['controller_file_suffix'];
        require_once $path;

        // Create a controller object from the class we just included.
        $class  = ucfirst($this->controller_name) . $o['controller_class_suffix'];
        $object = new $class;

        // Find out the method name, with or without optional underscore `_` before its name.
        $method = method_exists($object, $this->action_name) ? $this->action_name : '_' . $this->action_name;

        // Get the requested method of the controller and return it.
        return (new ReflectionMethod($object, $method))->getClosure($object);
    }

}