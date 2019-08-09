<?php declare(strict_types=1);

/*
 * Copyright 2018-2019 Tamas Bolner
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


class CliCatcher {
    /**
     * @var bool
     */
    private static $action_found = false;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $description;

    /**
     * @var CliParameter[]
     */
    private $parameters;

    /**
     * CliCatcher constructor.
     * @param string $path
     * @param string $description
     */
    public function __construct(string $path, string $description = "")
    {
        $this->path = $path;
        $this->description = $description;
        $this->parameters = [];

        Doc::registerCliControl($path, $description);
    }

    /**
     * @param string $name
     * @param string $type
     * @param bool $isRequired
     * @param string $description
     * @return CliCatcher
     * @throws \Exception
     */
    public function addParameter(string $name, string $type, bool $isRequired, string $description = ""): CliCatcher {
        $name = trim($name);

        if ($name == "") {
            throw new \Exception("Router::cli()->addParameter(): Missing parameter name. Path: {$this->path}");
        }

        if (isset($this->parameters[$name])) {
            throw new \Exception("Parameter '{$name}' is set more than once.");
        }

        $param = new CliParameter($name, $isRequired, $type, $description);
        $param->validate();

        $this->parameters[$name] = $param;

        Doc::registerCliParameter($this->path, $name, $type, $isRequired, $description);

        return $this;
    }

    /**
     * @param callable $function
     * @throws \Exception
     */
    public function matchRoute(callable $function) {
        if (self::$action_found == true) {
            return;
        }

        global $argc, $argv;

        if (@trim((string)$argv[1]) != $this->path) {
            // No match
            return;
        }

        /*
         * Process parameters
         */
        for($i = 2; $i < $argc; $i++) {
            $expression = $argv[$i];
            $eq_pos = strpos($expression, '=');
            if ($eq_pos < 1) {
                throw new \Exception("The name=value format is required for all parameters. Path: {$this->path}.");
            }

            $param_name = trim(substr($expression, 0, $eq_pos));

            if (!isset($this->parameters[$param_name])) {
                throw new \Exception("Unknown parameter '{$param_name}'.");
            }

            $value = trim(substr($expression, $eq_pos + 1));
            $parameter = $this->parameters[$param_name];

            if ($value == "" && $parameter->isRequired()) {
                throw new \Exception("Empty value for the required parameter '{$parameter->getName()}'.");
            }

            if ($value != "") {
                $value = preg_replace('/^[\"\\\']{1,1}/', '', $value);
                $value = preg_replace('/[\"\\\']{1,1}$/', '', $value);

                $parameter->setValue($value);
                $parameter->validate();
            }
        }

        /*
         * Call function
         */
        $pass_params = [];

        foreach ($this->parameters as $parameter) {
            if ($parameter->getValue() == "") {
                if ($parameter->isRequired()) {
                    throw new \Exception("Empty value for the required parameter '{$parameter->getName()}'.");
                }

                $pass_params[] = null;
            } else {
                $pass_params[] = $parameter->getCastedValue();
            }
        }

        Router::callBeforeExecCallback(false, $this->path);
        call_user_func_array($function, $pass_params);

        self::$action_found = true;
    }

    /**
     * @return bool
     */
    public static function isActionFound(): bool
    {
        return self::$action_found;
    }
}
