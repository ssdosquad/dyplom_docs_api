<?php

function auth() {
	global $currentOptions, $session_standing;

	$login    = verify_field("Логин", $currentOptions['login'], 0, 45);
	$password = verify_field("Пароль", $currentOptions['password'], 0, 0);

	if (!($query = dbQueryOne("SELECT id, password FROM account WHERE login = '{$login}'"))) {
		send_answer(["Аккаунт с введённым логином отсутствует"]);
	}

	if (!password_verify($password, $query['password'])) {
		send_answer(["Введён неверный пароль"]);
	}

	$account_id   = $query['id'];
	$session_key  = create_session($account_id);
	$session_time = time();
	$ip           = $_SERVER["REMOTE_ADDR"];

	if (!dbExecute("INSERT INTO account_session (account_id, session_key, session_time, ip) VALUES ('{$account_id}', '{$session_key}', '{$session_time}', '{$ip}')")) {
		send_answer(["Неизвестная ошибка создания новой сессии"]);
	}

	send_answer(["session_key" => $session_key, "session_time" => $session_standing], true);
}

function get_deal_all() {
	$deals = dbQuery("SELECT * FROM deal");
	send_answer($deals, true);
}

function get_deal_by_category() {
	global $currentOptions;
	$category_id = $currentOptions['category_id'];
	$deals       = dbQuery("SELECT deal.* FROM deal, deal_category WHERE deal.id = deal_category.deal_id AND deal_category.category_id = '{$category_id}'");
	send_answer($deals, true);
}

function new_deal() {
	global $currentOptions;
	$fullname        = verify_field("ФИО", $currentOptions['fullname'], 3, 600);
	$date_born       = verify_field("Дата рождения", $currentOptions['date_born'], 2, 45);
	$passport_series = verify_field("Серия паспорта", $currentOptions['passport_series'], 1, 11);
	$passport_id     = verify_field("Номер паспорта", $currentOptions['passport_id'], 1, 11);
	$passport_issued = verify_field("Кем выдан паспорт", $currentOptions['passport_issued'], 1, 120);
	$passport_date   = verify_field("Дата выдачи", $currentOptions['passport_date'], 1, 45);
	$short_text      = verify_field("Краткое описание дела", $currentOptions['short_text'], 1, 45);

	$documents = [];
	for ($i = 0; $i < 20; $i++) {
		if (isset($_FILES['document'.$i]) && $_FILES['document'.$i]["name"] != null) {
			$documents[] = $_FILES['document'.$i];
		} else {
			break;
		}
	}

	if (!dbExecute("INSERT INTO deal (fullname, date_born, passport_series, passport_id, passport_issued, passport_date, short_text) VALUES ('{$fullname}', '{$date_born}', '{$passport_series}', '{$passport_id}', '{$passport_issued}', '{$passport_date}', '{$short_text}')")) {
		send_answer(["Неизвестная ошибка записи нового дела в базу"]);
	}

	$deal_id = dbLastId();

	$warrings = [];
	if ($documents != []) {
		$sql_to_execute = "INSERT INTO deal_document (deal_id, path) VALUES ";
		$i              = 0;
		foreach ($documents as $document) {
			$extentions = array_slice(explode(".", $document['name']), -1)[0];
			$path       = "/document/".time()."_".$i.".".$extentions;
			if (upload_file($path, $document)) {
				$sql_to_execute .= "('{$deal_id}', '{$path}')";
				if ($i < count($documents)-1) {$sql_to_execute .= ", ";
				}
			} else {
				$warrings[] = "Документ ({$i}) не был загружен";
			}
			$i++;
		}
		if (!dbExecute($sql_to_execute)) {
			$warrings[] = "Документы не были прикреплены";
		}
	}

	send_answer($warrings, true);
}

function get_deal() {
	global $currentOptions;
	$deal_id = $currentOptions['id'];
	if ($deal = dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}'")) {
		$deal['documents'] = dbQuery("SELECT path FROM deal_document WHERE deal_id = '{$deal_id}'");
		$deal['categorys'] = dbQuery("SELECT category.* FROM deal_category, category WHERE deal_category.deal_id = '{$deal_id}' AND category.id = deal_category.category_id");
		send_answer($deal, true);
	}
	send_answer(["Дело с указанным ID не найдено"]);
}

function get_category_all() {
	$categorys = dbQuery("SELECT * FROM category");
	send_answer(["categorys" => $categorys], true);
}