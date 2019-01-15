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


class Doc {
    /**
     * @var array
     */
    private static $cli;

    public static function init() {
        self::$cli = [];
    }

    /**
     * @param string $path
     * @param string $description
     */
    public static function registerCliControl(string $path, string $description) {
        self::$cli[$path]["description"] = $description;
    }

    /**
     * @param string $path
     * @param string $name
     * @param string $type
     * @param bool $isRequired
     * @param string $description
     */
    public static function registerCliParameter(string $path, string $name, string $type, bool $isRequired,
                                                string $description) {

        if (!isset(self::$cli[$path]["parameters"])) {
            self::$cli[$path]["parameters"] = [];
        }

        self::$cli[$path]["parameters"][] = [
            "name" => $name,
            "type" => $type,
            "is_required" => $isRequired,
            "description" =>$description
        ];
    }

    /**
     * @return string
     */
    public static function getCliDoc(): string {
        $output = [];

        foreach (self::$cli as $path => $control) {
            $output[] = "{$path}: {$control["description"]}";

            if (isset($control["parameters"])) {
                foreach ($control["parameters"] as $parameter) {
                    if (!$parameter["is_required"]) {
                        $opt = " (optional)";
                    } else {
                        $opt = "";
                    }

                    $output[] = "\t- {$parameter["name"]} [{$parameter["type"]}]: {$parameter["description"]}{$opt}";
                }
            }

            $output[] = "";
        }

        return implode("\n", $output);
    }
}
