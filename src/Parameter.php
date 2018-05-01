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


class Parameter {
    const TYPE_STRING = 'string';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOL = 'bool';

    const ALL_TYPES = [
        self::TYPE_STRING, self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_BOOL
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    /**
     * RouterParameter constructor.
     * @param string $name
     * @param string $type
     * @param string $value
     */
    public function __construct(string $name, string $type, string $value)
    {
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @throws \Exception
     */
    public function validate() {
        if (!in_array($this->type, self::ALL_TYPES)) {
            throw new \Exception("Invalid parameter type '{$this->type}'. Allowed types: ".implode(', ', self::ALL_TYPES));
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getCastedValue() {
        switch ($this->type) {
            case self::TYPE_STRING:
                return $this->value;
            case self::TYPE_INT:
                return (int)$this->value;
            case self::TYPE_FLOAT:
                return (float)$this->value;
            case self::TYPE_BOOL: {
                if (in_array(strtolower($this->value),
                    ['true', 'on', '1', 'yes', 'enabled'])) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        return null;
    }
}
