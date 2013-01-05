<?php
namespace database;
// register class auto loader for this namespace
spl_autoload_register(function($class) {
    $namespace = substr($class, 0, strripos($class, "\\"));
    if(!empty($namespace)) {
        $class = substr($class, strripos($class, "\\")+1);
    }

    if($namespace == __NAMESPACE__) {
        require __DIR__ . "/". $class.".php";
    }
});
