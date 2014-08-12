<?php

//
// MarleyTemplate class wraps-around a template directrory and 
// then loads and compiles templates and layouts from that directory.
//
// You can override default options from the constructor or
// directly from the find_and_render() method.
//

class MarleyTemplate {

    //
    // Array of default options.
    //
    private $options = [
        'root_dir'          => '',
        'templates_dir'     => '/views',
        'templates_sub_dir' => '',
        'layouts_dir'       => '/views/layouts',
        'layout'            => 'main',
        'extension'         => '.html.php',
        'data'              => [],
        'context'           => null
    ];


    //
    // Construct a template instance by specifing its options.
    // Only required option is 'template', defaults will be used for others.
    // Directory root is set to $_SERVER['DOCUMENT_ROOT'], 
    // but can be overriden from the passsed options.
    //
    public function __construct($options = []) {
        $this->options['root_dir'] = $_SERVER['DOCUMENT_ROOT'];
        $this->options = array_merge($this->options, $options);
    }


    //
    // Find and render a template file with optional layout.
    // If layout is specified, template will be inserted inside it.
    //
    // Each key/value pair inside `data` array/object will become local variables inside a template
    // and $this inside a template will refer to the passed `context` object.
    //
    public function find_and_render($template_name, $options = []) {
        $options = array_merge($this->options, $options);
        $template_path = $this->template_path($template_name, $options);
        
        if ($options['layout']) {
            $layout_path = $this->layout_path($options['layout'], $options);
            $layout = $this->render($layout_path, $options['data'], $options['context']);
        }

        $template = $this->render($template_path, $options['data'], $options['context']);
        $content  = $layout ? str_replace('{{yield}}', $template, $layout) : $template;
        
        return $content;
    }


    //
    // Render a template and return the resulting content.
    //
    // $data is an associative array whose keys/values will be local variables inside a template.
    // $context is an object which becomes $this inside a template file.
    //
    public function render($file_path, $data = [], $context = null) {
        $f = $this->compile($file_path);

        if($context) {
            $f = $f->bindTo($context);
        }

        $content = $f($data);

        return $content;
    }


    //
    // Create a anonymouse function that wraps-over a template file.
    // This will allow us to specify template data and context later.
    //
    public function compile($file_path) {
        if(file_exists($file_path)) {
            return function($data) use (&$file_path) {
                extract($data, EXTR_SKIP);
                ob_start();
                include $file_path;
                return ob_get_clean();
            };   
        } else {
            throw new InvalidArgumentException('File ' . $file_path . ' does not exists.');
        }
    }


    //
    // Determine a template path (Inspired by: http://guides.rubyonrails.org/layouts_and_rendering.html)
    // There are only 3 types of paths used:
    // 1. An absolute path.
    // 2. A path relative to the templates directory.
    // 3. A path relative to a sub directory inside the templates direactory.
    //
    private function template_path($path, $options) {
        $templates_root_directory = $options['root_dir'] . $options['templates_dir'] . '/';
        $path = $this->append_extension($path, $options);

        // If a path starts with a slash `/`, we assume it's an absolute path.
        if (preg_match('/^\//', $path)) {
            return $path;
        }

        // If a path doesn't include a slash `/` at all and the `templates_sub_dir`
        // option is specified, we assume it's a relative path to that sub-directory.
        else if ($options['templates_sub_dir'] && !preg_match('/\//', $path)) {
            return $templates_root_directory . $options['templates_sub_dir'] . '/' . $path;
        }

        // For everything else, including paths that include a slash `/` inside it,
        // we assume it's a relative path to the templates directory.
        else {
            return $templates_root_directory . $path;
        }
    }


    //
    // Determine a layout path.
    //
    private function layout_path($path, $options) {
        $layouts_root_directory = $options['root_dir'] . $options['layouts_dir'] . '/';
        $path = $this->append_extension($path, $options);
        return $layouts_root_directory . $path;
    }


    //
    // Append extension to a path only if it doesn't already have one.
    //
    private function append_extension($path, $options) {
        $path_contains_extension = strpos($path, $options['extension']) !== false;
        return $path_contains_extension ? $path : $path . $options['extension'];
    }

}