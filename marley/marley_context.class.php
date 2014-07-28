<?php

class MarleyContext {

    //
    // Default options of a context object. 
    // Mostly used by the render() method for specifing default template directory for controllers.
    //
    private $options;


    //
    // Names with boolean values of all rendered templates.
    // This is used to prevent rendering the same template twice.
    //
    private $rendered = [];


    //
    // Data that becomes local variables inside a template.
    //
    public $data;


    //
    // Construct a context object with optional template options.
    //
    public function __construct($options = []) {
        $this->options = $options;
        $this->data = new stdClass();
    }


    //
    // Set a new value for any global option.
    //
    public function set_option($name, $value) {
        $this->options[$name] = $value;
    }


    //
    // Checks if template is already rendered or not.
    //
    public function is_rendered($template_name) {
        return $rendered[$template_neme];
    }


    //
    // Render a template inside a callback function.
    //
    public function render($template_neme, $options = []) {
        if (!$this->is_rendered($template_neme)) {
            // Syntactic sugar.
            $options['template'] = $template_neme;

            // Merge passed options to the options specified during construction.
            $options = array_merge($this->options, $options);

            $template = new MarleyTemplate($options);
            $template->render((array)$this->data, $this);

            $rendered[$template_neme] = true;
        }
    }
}