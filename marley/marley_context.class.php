<?php

//
// MarleyContext class provides is an object that is shared 
// between a controller/callback and a template.
//
// All helper methods, shared objects, plugins and etc.
// are attached to this object.
//

class MarleyContext {

    //
    // Default options of a context object.
    //
    private $options;


    //
    // Data object whose properties become local variables inside a template.
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
    // Render (print) a template.
    //
    // $params may be a template name or options array.
    // If it's an array, the $render_options array is ignored.
    //
    public function render($params, $render_options = []) {
        if (is_array($params)) {
            $options = array_merge($this->options, $params);
        } else if (is_string($params)) {
            $template_neme = $params;
            $options = array_merge($this->options, $render_options);
        } else {
            throw new InvalidArgumentException('render() function\'s parameter should be an array or a string.');
        }
        
        $options['context']  = $options['context']  ?: $this;
        $options['status']   = $options['status']   ?: 200;

        if ($options['json']) {
            header('Content-type: application/json');
            print json_encode($options['json'], JSON_PRETTY_PRINT);

        } else if ($options['js']) {
            header('Content-type: text/javascript', true, $options['status']);
            print $options['js'];
            
        } else if ($options['plain']) {
            header('Content-type: text/plain', true, $options['status']);
            print $options['plain'];

        } else if ($options['html']) {
            header('Content-type: text/html', true, $options['status']);
            print $options['html'];

        } else if ($options['partial']) {
            $options['layout'] = $options['layout'] ?: false;
            $options['continue'] = isset($options['continue']) ? $options['continue'] : true;

            $template = new MarleyTemplate($options);
            $html = $template->find_and_render($options['partial']);
            print $html;

        } else {
            header('Content-type: text/html', true, $options['status']);
            $options['data'] = $options['data'] ?: (array)$this->data;
            
            $template = new MarleyTemplate($options);
            $html = $template->find_and_render($options['template'] ?: $template_neme);
            print $html;
        }

        if (!$options['continue']) {
            exit;
        }
    }


    //
    // Get the base url of the website including subdomain and port number.
    //
    private function base_url() {
        $protocol = strpos(getenv('SERVER_PROTOCOL'), 'HTTPS') !== false ? 'https' : 'http';
        $base_url = $protocol . '://' . getenv('HTTP_HOST');
        return $this->options['base_url'] ?: $base_url;
    }


    //
    // Create an url with passed route, based on the site's base url. 
    //
    public function url($path = '') {
        $does_not_needs_slash = ($path === '' || strpos($path, '/') === 0);
        return $this->base_url() . ($does_not_needs_slash ? $path : '/' . $path);
    }


    //
    // Redirect using HTTP header.
    //
    function redirect($path = '', $time = 0) {
        if($time > 0) {
            header('refresh:' . $time . ';url=' . $this->url($path));
        } else {
            header('Location: ' . $this->url($path), true);
            exit;
        }
    }


    //
    // Call a dynamically assigned function.
    // This is a workaround, because PHP's interpreter can't call
    // anonymouse functions assigned as object's properties.
    //
    public function __call($name, $arguments) {
        if ($this->$name && is_callable($this->$name)) {
            call_user_func_array($this->$name, $arguments);
        };
    }

}