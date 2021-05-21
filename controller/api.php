<?php

function auth(){
    global $currentOptions, $session_standing;

    $login = verify_field("Логин", $currentOptions['login'], 0, 45);
    $password = verify_field("Пароль", $currentOptions['password'], 0, 0);

    if(!($query = dbQueryOne("SELECT id, password FROM account WHERE login = '{$login}'"))){
        send_answer(["Аккаунт с введённым логином отсутствует"]);
    }

    if(!password_verify($password, $query['password'])){
        send_answer(["Введён неверный пароль"]);
    }

    $account_id = $query['id'];
    $session_key = create_session($account_id);
    $session_time = time();
    $ip = $_SERVER["REMOTE_ADDR"];

    if(!dbExecute("INSERT INTO account_session (account_id, session_key, session_time, ip) VALUES ('{$account_id}', '{$session_key}', '{$session_time}', '{$ip}')")){
        send_answer(["Неизвестная ошибка создания новой сессии"]);
    }

    send_answer(["session_key" => $session_key, "session_time" => $session_standing], true);
}