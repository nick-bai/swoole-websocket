<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/5/4
 * Time: 上午9:12
 */

namespace Toolkit\Cli;

/**
 * Class Color
 * @package Toolkit\Cli
 * // basic
 * @method string red(string $text)
 * @method string blue(string $text)
 * @method string cyan(string $text)
 * @method string black(string $text)
 * @method string brown(string $text)
 * @method string green(string $text)
 * @method string white(string $text)
 * @method string yellow(string $text)
 * @method string magenta(string $text)
 *
 * // alert
 * @method string info(string $text)
 * @method string danger(string $text)
 * @method string error(string $text)
 * @method string notice(string $text)
 * @method string warning(string $text)
 * @method string success(string $text)
 *
 * // more please @see Color::STYLES
 */
class Color
{
    const NORMAL = 0;

    // Foreground color
    const FG_BLACK = 30;
    const FG_RED = 31;
    const FG_GREEN = 32;
    const FG_BROWN = 33; // like yellow
    const FG_BLUE = 34;
    const FG_CYAN = 36;
    const FG_WHITE = 37;
    const FG_DEFAULT = 39;

    // extra Foreground color
    const FG_DARK_GRAY = 90;
    const FG_LIGHT_RED = 91;
    const FG_LIGHT_GREEN = 92;
    const FG_LIGHT_YELLOW = 93;
    const FG_LIGHT_BLUE = 94;
    const FG_LIGHT_MAGENTA = 95;
    const FG_LIGHT_CYAN = 96;
    const FG_WHITE_W = 97;

    // Background color
    const BG_BLACK = 40;
    const BG_RED = 41;
    const BG_GREEN = 42;
    const BG_BROWN = 43; // like yellow
    const BG_BLUE = 44;
    const BG_CYAN = 46;
    const BG_WHITE = 47;
    const BG_DEFAULT = 49;

    // extra Background color
    const BG_DARK_GRAY = 100;
    const BG_LIGHT_RED = 101;
    const BG_LIGHT_GREEN = 102;
    const BG_LIGHT_YELLOW = 103;
    const BG_LIGHT_BLUE = 104;
    const BG_LIGHT_MAGENTA = 105;
    const BG_LIGHT_CYAN = 106;
    const BG_WHITE_W = 107;

    // color option
    const BOLD = 1;      // 加粗
    const FUZZY = 2;      // 模糊(不是所有的终端仿真器都支持)
    const ITALIC = 3;      // 斜体(不是所有的终端仿真器都支持)
    const UNDERSCORE = 4;  // 下划线
    const BLINK = 5;      // 闪烁
    const REVERSE = 7;    // 颠倒的 交换背景色与前景色
    const CONCEALED = 8;      // 隐匿的

    /**
     * some styles
     * custom style: fg;bg;opt
     * @var array
     */
    const STYLES = [
        // basic
        'red' => '0;31',
        'blue' => '0;34',
        'cyan' => '0;36',
        'black' => '0;30',
        'green' => '0;32',
        'brown' => '0;33',
        'white' => '1;37',
        'normal' => '39',// no color
        'yellow' => '1;33',
        'magenta' => '1;35',

        // alert
        'suc' => '1;32',// same 'green' and 'bold'
        'success' => '1;32',
        'info' => '0;32',// same 'green'
        'comment' => '0;33',// same 'brown'
        'note' => '36;1',
        'notice' => '36;4',
        'warn' => '0;30;43',
        'warning' => '0;30;43',
        'danger' => '0;31',// same 'red'
        'err' => '30;41',
        'error' => '30;41',

        // more
        'lightRed' => '1;31',
        'light_red' => '1;31',
        'lightGreen' => '1;32',
        'light_green' => '1;32',
        'lightBlue' => '1;34',
        'light_blue' => '1;34',
        'lightCyan' => '1;36',
        'light_cyan' => '1;36',
        'lightDray' => '37',
        'light_gray' => '37',

        'darkDray' => '90',
        'dark_gray' => '90',
        'lightYellow' => '93',
        'light_yellow' => '93',
        'lightMagenta' => '95',
        'light_magenta' => '95',

        // extra
        'lightRedEx' => '91',
        'light_red_ex' => '91',
        'lightGreenEx' => '92',
        'light_green_ex' => '92',
        'lightBlueEx' => '94',
        'light_blue_ex' => '94',
        'lightCyanEx' => '96',
        'light_cyan_ex' => '96',
        'whiteEx' => '97',
        'white_ex' => '97',

        // option
        'bold' => '1',
        'underscore' => '4',
        'reverse' => '7',
    ];

    /**
     * Regex to match tags
     */
    const COLOR_TAG = '/<([a-z=;]+)>(.*?)<\/\\1>/s';

    /**
     * Regex used for removing color codes
     */
    const STRIP_TAG = '/<[\/]?[a-zA-Z=;]+>/';

    /**
     * CLI color template
     */
    const COLOR_TPL = "\033[%sm%s\033[0m";

    /**
     * @param string $method
     * @param array $args
     * @return string
     */
    public static function __callStatic($method, array $args)
    {
        if (isset(self::STYLES[$method])) {
            return self::render($args[0], $method);
        }

        return '';
    }

    /*******************************************************************************
     * color render
     ******************************************************************************/

    /**
     * apply style for text
     * @param string $style
     * @param string $text
     * @return string
     */
    public static function apply(string $style, string $text): string
    {
        return self::render($text, $style);
    }

    /**
     * render text
     * @param string $text
     * @param string|array $style
     * - string: 'green', 'blue'
     * - array: [Color::FG_GREEN, Color::BG_WHITE, Color::UNDERSCORE]
     * @return string
     */
    public static function render(string $text, $style = null): string
    {
        if (!$text) {
            return $text;
        }

        if (!Cli::isSupportColor()) {
            return self::clearColor($text);
        }

        // use defined style: 'green'
        if (\is_string($style)) {
            $color = self::STYLES[$style] ?? '0';
            // custom style: [self::FG_GREEN, self::BG_WHITE, self::UNDERSCORE]
        } elseif (\is_array($style)) {
            $color = \implode(';', $style);

            // user color tag: <info>message</info>
        } elseif (\strpos($text, '<') !== false) {
            return self::parseTag($text);
        } else {
            return $text;
        }

        // $result = chr(27). "$color{$text}" . chr(27) . chr(27) . "[0m". chr(27);
        return \sprintf(self::COLOR_TPL, $color, $text);
    }

    /**
     * parse color tag e.g: <info>message</info>
     * @param string $text
     * @return mixed|string
     */
    public static function parseTag(string $text)
    {
        if (!$text || false === \strpos($text, '<')) {
            return $text;
        }

        // if don't support output color text, clear color tag.
        if (!Cli::isSupportColor()) {
            return static::clearColor($text);
        }

        if (!\preg_match_all(self::COLOR_TAG, $text, $matches)) {
            return $text;
        }

        foreach ((array)$matches[0] as $i => $m) {
            if ($style = self::STYLES[$matches[1][$i]] ?? null) {
                $tag = $matches[1][$i];
                $match = $matches[2][$i];

                $replace = \sprintf("\033[%sm%s\033[0m", $style, $match);
                $text = \str_replace("<$tag>$match</$tag>", $replace, $text);
            }
        }

        return $text;
    }

    /**
     * wrap a style tag
     * @param string $string
     * @param string $tag
     * @return string
     */
    public static function wrapTag(string $string, string $tag): string
    {
        if (!$string) {
            return '';
        }

        if (!$tag) {
            return $string;
        }

        return "<$tag>$string</$tag>";
    }

    /**
     * @param string $text
     * @param bool $stripTag
     * @return string
     */
    public static function clearColor(string $text, bool $stripTag = true): string
    {
        // return preg_replace('/\033\[(?:\d;?)+m/', '' , "\033[0;36mtext\033[0m");
        return (string)\preg_replace(
            '/\033\[(?:\d;?)+m/',
            '',
            $stripTag ? \strip_tags($text) : $text
        );
    }

    /**
     * Strip color tags from a string.
     * @param string $string
     * @return mixed
     */
    public static function stripTag(string $string): string
    {
        // $text = strip_tags($text);
        return (string)\preg_replace(self::STRIP_TAG, '', $string);
    }

    /**
     * @param string $style
     * @return bool
     */
    public static function hasStyle(string $style): bool
    {
        return isset(self::STYLES[$style]);
    }

    /**
     * get all style names
     * @return array
     */
    public static function getStyles(): array
    {
        return array_filter(\array_keys(self::STYLES), function ($style) {
            return !\strpos($style, '_');
        });
    }
}
