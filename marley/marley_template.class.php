<?php

class MarleyTemplate {

    //
    // Array of template options. 
    // These values can be changed from the constructor.
    //
    private $options = [
        'root_dir'     => '',
        'template_dir' => '/views',
        'template'     => '',
        'layout_dir'   => '/views/layouts',
        'layout'       => 'main',
        'data'         => [],
        'context'      => null,
        'extension'    => '.html.php'
    ];


    //
    // Construct a template instance by specifing its options.
    // Only required option is 'template', defaults will be used for others.
    // Directory root is set to $_SERVER['DOCUMENT_ROOT'], 
    // but can be overriden from the passsed options.
    //
    public function __construct($options) {
        $this->options['root_dir'] = $_SERVER['DOCUMENT_ROOT'];

        if (is_array($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }


    //
    // Print a template.
    // If layout is specified, template will be inserted inside it.
    //
    // Each key/value pair inside $data array/object will become local variables inside a template
    // and $this inside a template will refer to the passed $context object.
    //
    public function render($data = [], $context = null) {
        $o = $this->options;

        if ($o['layout']) {
            $layout = $this->compile($o['layout_dir'], $o['layout'], $data, $context);
        }

        $template = $this->compile($o['template_dir'], $o['template'], $data, $context);

        $html = $layout ? str_replace('{{yield}}', $template, $layout) : $template;
        print $html;
    }


    //
    // Compile a template file and return the resulting content.
    //
    // $data is an associative array whose keys/values will be local variables inside a template.
    // $context is an object which becomes $this inside a template file.
    //
    private function compile($dir, $name, $data, $context) {
        $f = $this->template($dir, $name);
        
        if ($context && is_object($contex)) {
            $f = $f->bindTo($context);
        }

        $content = $f($data);

        return $content;
    }


    //
    // Create a anonymouse function that wraps-over a template file.
    // This will allow us to specify template data later.
    //
    private function template($dir, $name) {
        $path = $this->options['root_dir'] . $dir. '/' . $name . $this->options['extension'];

        if(!file_exists($path) || !is_readable($path)) {
            throw new InvalidArgumentException('File ' . $name . ' does not exists or it\'s not readable.');
        } else {
            return function($data) use (&$path) {
                extract($data, EXTR_SKIP);
                ob_start();
                include $path;
                return ob_get_clean();
            };
        }
    }

}