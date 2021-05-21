<?php

$config_db = load_config("db");
$link = null;

try{
    $link = mysqli_connect($config_db["host"], $config_db["user"], $config_db["password"], $config_db["name"]);
}catch(Exception $e){
    exit("DB connection incorrect");
}

function dbQuery($sql){
    global $link;
    if($result_query = mysqli_query($link, $sql)){
        $result = [];
        while ($row = mysqli_fetch_assoc($result_query)) {
            $result[] = $row;
        }
        mysqli_free_result($result_query);
        if($result != []) return $result;
    }
    return false;
}

function dbQueryOne($sql){
    global $link;
    if($result_query = mysqli_query($link, $sql." LIMIT 1")){
        $result = mysqli_fetch_assoc($result_query);
        mysqli_free_result($result_query);
        return $result;
    }
    return false;
}

function dbExecute($sql){
    global $link;
    if(mysqli_query($link, $sql)){
        return true;
    }
    return false;
}

function dbLastId(){
    global $link;
    return mysqli_insert_id($link);
}