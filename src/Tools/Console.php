<?php

namespace Aventus\Laraventus\Tools;



class Console
{
    public static function startLog()
    {
        ob_start();
    }
    public static function stopLog()
    {
        $a = ob_get_contents();
        ob_clean();
        error_log($a);
    }
    /**
     * Summary of log
     * @param $callback
     * @return void
     */
    public static function logFct($callback)
    {
        Console::startLog();
        $callback();
        Console::stopLog();
    }

    /**
     * @param $txt
     * @return void
     */
    public static function log($txt)
    {
        Console::logFct(function () use ($txt) {
            echo $txt;
        });
    }

    public static function dump($data)
    {
        Console::logFct(function () use ($data) {
            var_dump($data);
        });
    }

    public static function trace()
    {
        Console::logFct(function () {
            debug_print_backtrace();
        });
    }
}
