<?php declare(strict_types=1);

/*
 * Copyright 2018 Tamas Bolner
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace FlexPhpRouter;


/**
 * Class Router
 * @package Adlib\RenderScheduler\Framework
 */
class Router {

    /**
     * @var null|string
     */
    private static $selected_controller = null;

    /**
     * @var bool
     */
    private static $action_found = false;

    /**
     * @var string|null
     */
    private static $uri = null;

    /**
     * @param string $controller_dir
     * @param string $default_controller
     * @param string $apiPrefix
     * @throws \Exception
     */
    public static function route(string $controller_dir, string $default_controller, string $apiPrefix = "") {
        if (php_sapi_name() == "cli") {
            global $argc, $argv;

            if ($argc < 2) {
                throw new \Exception("Please supply the command path in the first parameter.");
            }

            $uri = $argv[1];
        } else {
            $uri = trim((string)$_SERVER["REQUEST_URI"]);
        }

        /*
         * Remove all characters except the allowed ones (security)
         * Allowed characters:
         *      a-z, A-Z, 0-9, - (dash), _ (underline)
         */
        $qm_pos = strpos($uri, '?');
        if ($qm_pos !== false) {
            $uri = substr($uri, 0, $qm_pos);
        }
        $uri = preg_replace('/[^a-z0-9\-_\/]/i', '', $uri);
        $uri = preg_replace('/^\//i', '', $uri); // remove starting slash

        if ($apiPrefix != "") {
            $apiPrefix = preg_replace('/^\//i', '', $apiPrefix); // remove starting slash
            if (substr($uri, 0, strlen($apiPrefix)) == $apiPrefix) {
                $uri = substr($uri, strlen($apiPrefix));
                $uri = preg_replace('/^\//i', '', $uri); // remove starting slash
            }
        }

        self::$uri = '/'.$uri;
        $pos = strpos($uri, '/');

        /*
         * Load the controller.
         */
        if ($pos === false) {
            $main = $uri;
        } elseif ($pos > 0) {
            $main = substr($uri, 0, $pos);
        } else {
            throw new \Exception("Unknown route.");
        }

        if ($main == "") {
            $main = $default_controller;
        }

        $current = $main;

        while (true) {
            // TODO

            $controller = realpath("{$controller_dir}/{$current}.php");
            $found = true;

            if (!is_string($controller)) {
                if ($current != $default_controller) {
                    $current = $default_controller;
                    $controller = realpath("{$controller_dir}/{$current}.php");

                    if (!is_string($controller)) {
                        $found = false;
                    }
                } else {
                    $found = false;
                }
            }

            if (!$found) {
                if ($current != $default_controller) {
                    $current = $default_controller;
                    continue;
                } else {
                    throw new \Exception("Unknown route. Controller '{$main}' not found.");
                }
            }

            self::$selected_controller = $current;

            require($controller);

            /*
             * If this point is reached, then no action was executed.
             */
            if (php_sapi_name() == "cli") {
                if (!CliCatcher::isActionFound()) {
                    if ($current == $default_controller) {
                        throw new \Exception("No matching action found in CLI controller '{$main}'. Please double check the path, provided in the first parameter.");
                    } else {
                        $current = $default_controller;
                        continue;
                    }
                }
            } elseif (!self::$action_found) {
                if ($current == $default_controller) {
                    throw new \Exception("No matching action found in controller '{$main}'. Please double check the HTTP request method and the URI.");
                } else {
                    $current = $default_controller;
                    continue;
                }
            }
            
            break;
        }
    }

    /**
     * @param string $uriMask
     * @param callable $function
     * @noinspection PhpUnhandledExceptionInspection
     */
    public static function get(string $uriMask, callable $function) {
        self::parseUriParameters('GET', $uriMask, $function);
    }

    /**
     * @param string $uriMask
     * @param callable $function
     * @noinspection PhpUnhandledExceptionInspection
     */
    public static function post(string $uriMask, callable $function) {
        self::parseUriParameters('POST', $uriMask, $function);
    }

    /**
     * @param string $uriMask
     * @param callable $function
     * @noinspection PhpUnhandledExceptionInspection
     */
    public static function put(string $uriMask, callable $function) {
        self::parseUriParameters('PUT', $uriMask, $function);
    }

    /**
     * @param string $uriMask
     * @param callable $function
     * @noinspection PhpUnhandledExceptionInspection
     */
    public static function any(string $uriMask, callable $function) {
        self::parseUriParameters('ANY', $uriMask, $function);
    }

    /**
     * @param string $uriMask
     * @param callable $function
     * @noinspection PhpUnhandledExceptionInspection
     */
    public static function delete(string $uriMask, callable $function) {
        self::parseUriParameters('DELETE', $uriMask, $function);
    }

    /**
     * Careful: the 'patch' method is not fully supported by browsers and web servers.
     *
     * @param string $uriMask
     * @param callable $function
     * @noinspection PhpUnhandledExceptionInspection
     */
    public static function patch(string $uriMask, callable $function) {
        self::parseUriParameters('PATCH', $uriMask, $function);
    }

    /**
     * @param string $path
     * @return CliCatcher
     */
    public static function cli(string $path): CliCatcher {
        return new CliCatcher($path);
    }

    /**
     * @param Parameter[] $params
     * @param callable $function
     */
    private static function callAction(array $params, callable $function) {
        $pass_params = [];

        foreach ($params as $param) {
            $pass_params[] = $param->getCastedValue();
        }

        call_user_func_array($function, $pass_params);
    }

    /**
     * Returns null, if there was no match.
     * Returns an empty array [], if there was a match, but there are no parameters.
     * Returns an array of parameters (including their values) otherwise.
     *
     * @param string $http_method
     * @param string $uriMask
     * @param callable $function
     * @throws \Exception
     */
    private static function parseUriParameters(string $http_method, string $uriMask,
                                               callable $function) {

        if (self::$action_found) {
            return;
        }

        if (!is_string(self::$uri)) {
            throw new \Exception("A controller got executed without a call to Router::route(...).");
        }

        if ($http_method != 'ANY') {
            if ($_SERVER['REQUEST_METHOD'] !== $http_method) {
                return;
            }
        }

        if ($uriMask == "*") {
            self::$action_found = true;
            self::callAction([], $function);
            return;
        }

        try {
            $uri = self::$uri;
            $qm_pos = strpos($uri, '?');
            if ($qm_pos !== false) {
                $uri = substr($uri, 0, $qm_pos);
            }

            if ($uriMask[0] != '/') {
                $uriMask = '/'.$uriMask;
            }

            // Remove the ending slashes from both
            $uri = preg_replace('/[\/]+$/', '', $uri);
            $uriMask = preg_replace('/[\/]+$/', '', $uriMask);

            $parts = preg_split('/[\{\}]{1,1}/', $uriMask);

            if (count($parts) % 2 == 0) {
                throw new \Exception("Syntax error. Not all brackets have a pair.");
            }

            /**
             * @var Parameter[] $parameters
             */
            $parameters = [];

            /**
             * @var string[] $specifiers
             */
            $specifiers = [];

            foreach ($parts as $index => $part) {
                if ($index % 2) {
                    $param_parts = explode(':', $part);

                    $parameters[] = new Parameter(
                        $param_parts[0],
                        @(string)($param_parts[1]),
                        ''
                    );
                } else {
                    $specifiers[] = str_replace('/', '\/', preg_quote($part));
                }
            }

            $match_regex = '/^' . implode('(.*)', $specifiers) . '$/';
            $matches = [];
            preg_match($match_regex, $uri, $matches);

            if (count($matches) < 1) {
                return;
            }

            foreach ($parameters as $index => $parameter) {
                $parameters[$index]->setValue(@(string)($matches[$index + 1]));

                $parameter->validate();
            }
        } catch (\Exception $ex) {
            throw new \Exception("Controller: '".self::$selected_controller."', Action: '{$http_method} {$uriMask}'. Error message: ".$ex->getMessage());
        }

        self::callAction($parameters, $function);
        self::$action_found = true;
    }
}
