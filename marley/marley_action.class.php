<?php

//
// MarleyAction class wraps-around a controller class 
// or a callback function and provides functionality
// for calling and assigning $this context to the wrapped
// callback method/function.
//

class MarleyAction {

    //
    // Array of default, controller specific options.
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
    private $callback_closure;


    //
    // Public properties that expose information about the action object
    // and the type of callback we're dealing with.
    //
    public $is_controller;
    public $controller_object;
    public $controller_name;
    public $action_name;


    //
    // Set default options and create an internal reference to the requested callback function. 
    //
    public function __construct($callback, $options = []) {
        $this->options['root_dir'] = $_SERVER['DOCUMENT_ROOT'];
        $this->options = array_merge($this->options, $options);

        if (is_string($callback)) {
            $this->set_controller_info($callback);
            $this->initialize_controller();
            $this->callback_closure = $this->controller_callback();
        } else {
            $this->callback_closure = $callback;
        }

        if (!is_callable($this->callback_closure)) {
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
        $f = $this->callback_closure;

        if ($context) {
            $f = $f->bindTo($context);
        }

        if ($params) {
            call_user_func_array($f, $params);
        } else {
            $f();
        }
    }


    //
    // Extract controller and action names from a provided string.
    //
    // The string should be in this format: `controller#action`, 
    // where `controller` is a name (without suffix) of a controller class
    // and `action` is a name of a method inside that controller class.
    //
    private function set_controller_info($str) {
        $controller_parts      = explode('#', $str);
        $this->controller_name = $controller_parts[0];
        $this->action_name     = $controller_parts[1];
        $this->is_controller   = true;
    }


    //
    // Dynamically include controller class and initialize it.
    //
    private function initialize_controller() {
        require_once $this->controller_path();
        $class = ucfirst($this->controller_name) . $this->options['controller_class_suffix'];
        $this->controller_object = new $class;
    }


    //
    // Extract the requested callback function from a controller object.
    //
    private function controller_callback() {
        $method_name = str_replace('-', '_', $this->action_name);
        $method = new ReflectionMethod($this->controller_object, $method_name);
        return $method->getClosure($this->controller_object);
    }


    //
    // Determine a controller file path.
    //
    private function controller_path() {
        $controller_directory = $this->options['root_dir'] . $this->options['controller_dir'] . '/' ;
        $controller_filename  = $this->controller_name . $this->options['controller_file_suffix'];
        return $controller_directory . $controller_filename;
    }

}