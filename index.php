<?php

header('Content-Type: application/json; charset=utf-8');

require_once "init.php";

try{
    $route = getRoute(load_config("apiRoutes"));
    
    if( $route !== false ){
        load_controller($route);
    } else {
        load_error(404, "Страница не найдена");
    }
}catch(Exception $e){
    load_error(422, "Проблемы с загрузкой модулей");
}