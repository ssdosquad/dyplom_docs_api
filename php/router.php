<?php

$rootPath = "docs/";
$currentUrl = trim(array_shift(explode("?", $_SERVER["REQUEST_URI"])), "/");
$currentMethod = strtolower($_SERVER["REQUEST_METHOD"]);
$currentOptions = ($currentMethod == "get") ? $_GET : $_POST;

function getRoute($routesList){
    global $currentMethod, $currentOptions, $currentUrl, $level_access, $auth, $rootPath;

    foreach($routesList as $route){
        if($rootPath.trim($route["url"], "/") === trim($currentUrl, "/") && $route["method"] === $currentMethod){
            if(isset($route["level_access"])){
                if($route["level_access"] < $level_access){
                    load_error(401, "Доступ запрещён");
                    exit;
                }
            }

            if(isset($route['auth'])){
                if($route['auth'] != $auth){
                    load_error(401, "Неверный тип аутентификации");
                    exit;
                }
            }

            if(isset($route["fields"])){
                foreach($route["fields"] as $field){
                    if(empty($currentOptions[$field])){
                        send_answer(["Параметр '{$field}' не был отправлен"], false);
                    }
                }
            }

            return $route;
        }
    }
    return false;
}