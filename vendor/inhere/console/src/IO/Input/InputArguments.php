<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:11
 */

namespace Inhere\Console\IO\Input;

/**
 * Class InputArguments
 * - input arguments builder
 * @package Inhere\Console\IO\Input
 */
class InputArguments
{
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @param string $name
     * @param int|null $mode
     * @param string|null $type The argument data type. (eg: 'string', 'array', 'mixed')
     * @param string $description
     * @param null $default
     * @param null $alias
     */
    public function add(string $name, int $mode = null, string $type = null, string $description = '', $default = null, $alias = null)
    {

    }
}