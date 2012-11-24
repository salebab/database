<?php
namespace database;
// register class auto loader for this namespace
spl_autoload_register(function($class) {
    $namespace = substr($class, 0, strripos($class, "\\"));
    if($namespace == __NAMESPACE__) {
        require basename($class).".php";
    }
});

