<?php

namespace Aventus\Laraventus\Tools;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

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
        $a = str_replace(" ", " ", $a);
        $a = str_replace("\t", "    ", $a);
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

    private static function indentVarDump(string $dump): string
    {
        $lines = explode("\n", trim($dump));
        $level = 0;
        $out = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Diminue l'indent avant certaines fermetures
            if (preg_match('/^\}\)?$/', $line)) {
                $level = max(0, $level - 1);
            }

            $out[] = str_repeat("    ", $level) . $line;

            // Augmente l'indent après certaines ouvertures
            if (preg_match('/(array\(|object\(|\{)$/', $line)) {
                $level++;
            }
        }

        return implode("\n", $out);
    }
}
