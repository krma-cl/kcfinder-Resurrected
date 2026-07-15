<?php

/** 
 *   @desc Autoload Classes
 *   @package kcfinder-Resurrected
 *   @version 4.0
 *   @license http://opensource.org/licenses/GPL-3.0 GPLv3
 *   @license http://opensource.org/licenses/LGPL-3.0 LGPLv3
 */

spl_autoload_register(function ($path) {
    $path = explode("\\", $path);

    if (count($path) == 1)
        return;

    if ((count($path) > 2) && (strcasecmp($path[0], 'KCFinder') === 0)) {
        $relative = implode('/', array_slice($path, 1));
        if (preg_match('#^[A-Za-z0-9_/]+$#', $relative)) {
            $file = dirname(__DIR__) . "/src/$relative.php";
            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }

    list($ns, $class) = $path;

    if ($ns == "kcfinder") {
        if (in_array($class, array("uploader", "browser", "minifier", "session")))
            require "core/class/$class.php";
        elseif (file_exists("core/types/$class.php"))
            require "core/types/$class.php";
        elseif (file_exists("lib/class_$class.php"))
            require "lib/class_$class.php";
        elseif (file_exists("lib/helper_$class.php"))
            require "lib/helper_$class.php";
    } 
});
