<?php

class MarleyUrl {

    //
    // Private reference to the url we're trying to match against.
    //
    private $url;
    
    public function __construct($url) {
        $this->url = $url;
    }


    //
    // Match any character except slash `/`
    //
    // If user doesn't specifies a custom regex, this is the pattern 
    // that will be used to extract route parameter values from the url.
    //
    // Examples:
    //
    // url     => /artist/queen/innuendo
    // route   => /artist/:name/:album
    // values  => queen, innuendo
    //
    // url     => /track/4d61726c65792031393435
    // route   => /track/:id
    // value   => 4d61726c65792031393435
    //
    const DEFAULT_REGEX_PATTERN_FOR_EXTRACTING_PARAM_VALUES = '([^/]+)';


    //
    // Match colon `:` then any character except slash `/`
    //
    // Examples:
    //
    // route  => /artists/list/:name
    // match  => :name
    //
    // route  => /artists/list/:age([0-9]{3})
    // match  => :age([0-9]{3})
    //
    const REGEX_FOR_EXTRACTING_WHOLE_PARAMS = '/:[^\/]+/';


    //
    // Matches column `:` then any character except left parenthesis `(` and slash `/`
    //
    // Examples:
    //
    // param  => :name([a-z]+)
    // match  => :name
    //
    // param  => :age([0-9]{3})
    // match  => :age
    //
    const REGEX_FOR_EXTRACTING_PARAM_NAMES = '/:[^(\/]+/';


    //
    // Match left parenthesis `(` then any character except slash `/` and then right parenthesis `)`
    //
    // Examples:
    //
    // param  => :name([a-z]+)
    // match  => ([a-z]+)
    //
    // param  => :age([0-9]{3})
    // match  => ([0-9]{3})
    //
    const REGEX_FOR_EXTRACTING_USER_SPECIFIED_REGEXES = '/\([^\/]+\)/';


    //
    // Match the url to a route.
    //
    // Return an array containing all information about the match,
    // including paramaters and their values or FALSE if there is no match.
    //
    // For example, 
    // if the url is `/track/4d61726c65792031393435` and the route is `/track/:id`,
    // this function will return an array like this:
    //
    // [
    //    'url'    => '/track/4d61726c65792031393435',
    //    'route'  => '/track/:id',
    //    'params' => [':id' => '4d61726c65792031393435']
    // ]
    //
    public function match($route) {
        $route_regex  = $this->route_to_regex($route);
        $param_names  = $this->param_names($route);
        $matches      = [];

        if (preg_match($route_regex, $this->url, $matches)) {
            $match = [
                'url'   => $this->url,
                'route' => $route
            ];
            // If there're parameters
            if (count($matches) > 1) {
                $param_values    = array_slice($matches, 1);
                $match['params'] = array_combine($param_names, $param_values);
            }
            return $match;
        } else {
            return FALSE;
        }
    }


    //
    // Turn a route into a regex by replacing each parameter with a corresponding regex pattern.
    //
    // Examples:
    //
    // route    => /music/artists/:name
    // returns  => /^\/music\/artists\/([-\w]+)\/?/
    //
    // route    => /music/artists/:track_id([0-9]+)
    // returns  => /^\/music\/artists\/([0-9]+)\/?/
    //
    // route    => /music/artists/:name/:track_id([0-9]+)
    // returns  => /^\/music\/artists\/([-\w]+)\/([0-9]+)\/?/
    //
    private function route_to_regex($route) {
        // Replace each parameter with corresponding regex pattern.
        $regex = preg_replace_callback($this::REGEX_FOR_EXTRACTING_WHOLE_PARAMS, function($matches) {
            $param = $matches[0];
            return $this->param_to_regex($param);
        }, $route);

        // Escape slashes.
        $regex = str_replace('/', '\/', $regex);

        // Match from the start with an optional slash in the end.
        return '/^' . $regex . '\/?$/';
    }


    //
    // Turn a route parameter into a regex pattern.
    //
    // If user manually specified a regex pattern, we extract and return it,
    // otherwise we return a default pattern.
    //
    // Examples:
    //
    // param    => :name
    // returns  => ([^\]+)
    //
    // param    => :track_id([0-9]+)
    // returns  => ([0-9]+)
    //
    // param    => :album_name([a-zA-Z]+)
    // returns  => ([a-zA-Z]+)
    //
    private function param_to_regex($param) {
        $matches = [];

        if (preg_match($this::REGEX_FOR_EXTRACTING_USER_SPECIFIED_REGEXES, $param, $matches)) {
            $user_specified_regex = $matches[0];
            return $user_specified_regex;
        } else {
            return $this::DEFAULT_REGEX_PATTERN_FOR_EXTRACTING_PARAM_VALUES;
        }
    }


    //
    // Get all parameter names of a route as an array.
    //
    // Examples:
    //
    // route    => /music/artists/:name
    // returns  => [':name']
    //
    // route    => /music/artists/:name/:track_id([0-9]+)
    // returns  => [':name', ':track_id']
    //
    // route    => /msuic/new
    // returns  => []
    //
    private function param_names($route) {
        $matches = [];

        if (preg_match_all($this::REGEX_FOR_EXTRACTING_PARAM_NAMES, $route, $matches)) {
            $param_names = $matches[0]; // The first element is an array of full pattern matches.
            return $param_names;
        } else {
            return [];
        }
    }

}