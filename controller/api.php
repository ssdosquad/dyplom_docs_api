<?php

function auth() {
	global $currentOptions, $session_standing;

	$login    = verify_field("Логин", $currentOptions['login'], 0, 45);
	$password = verify_field("Пароль", $currentOptions['password'], 0, 0);

	if (!($query = dbQueryOne("SELECT * FROM account WHERE login = '{$login}'"))) {
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

	send_answer(["session_key" => $session_key, "session_time" => $session_standing, "account" => $query], true);
}

function get_deal_all() {
	$deals = dbQuery("SELECT * FROM deal");
	send_answer($deals, true);
}

function get_deal_by_category() {
	global $currentOptions;
	$category_id = $currentOptions['id'];
	$deals = dbQuery("SELECT deal.* FROM deal, deal_category WHERE deal.id = deal_category.deal_id AND deal_category.category_id = '{$category_id}'");
	send_answer($deals, true);
}

function new_deal() {
	global $currentOptions, $currentUser;
	$account_id = $currentUser["id"];
	$fullname = verify_field("ФИО", $currentOptions['fullname'], 3, 600);
	$date_born = verify_field("Дата рождения", $currentOptions['date_born'], 2, 45);
	$passport_series = verify_field("Серия паспорта", $currentOptions['passport_series'], 1, 11);
	$passport_id = verify_field("Номер паспорта", $currentOptions['passport_id'], 1, 11);
	$passport_issued = verify_field("Кем выдан паспорт", $currentOptions['passport_issued'], 1, 120);
	$passport_date = verify_field("Дата выдачи", $currentOptions['passport_date'], 1, 45);
	$short_text = verify_field("Краткое описание дела", $currentOptions['short_text'], 1, 45);
	$category_id = verify_field("Категория", $currentOptions['category'], 1, 11);

	$documents = [];
	for ($i = 0; $i < 20; $i++) {
		if (isset($_FILES['document'.$i]) && $_FILES['document'.$i]["name"] != null) {
			$documents[] = $_FILES['document'.$i];
		} else {
			break;
		}
	}

	if (!dbExecute("INSERT INTO deal (account_id, fullname, date_born, passport_series, passport_id, passport_issued, passport_date, short_text) VALUES ('{$account_id}', '{$fullname}', '{$date_born}', '{$passport_series}', '{$passport_id}', '{$passport_issued}', '{$passport_date}', '{$short_text}')")) {
		send_answer(["Неизвестная ошибка записи нового дела в базу"]);
	}

	$deal_id = dbLastId();

	if(!dbExecute("INSERT INTO deal_category (deal_id, category_id) VALUES ('{$deal_id}', '{$category_id}')")){
		send_answer(["Неизвестная ошибка связи дела с категорией"]);
	}

	$warrings = [];
	if ($documents != []) {
		$sql_to_execute = "INSERT INTO deal_document (deal_id, title, path) VALUES ";
		$i = 0;
		foreach ($documents as $document) {
			$boom_point = explode(".", $document['name']);
			$extentions = $boom_point[count($boom_point)-1];
			$filename = $boom_point[count($boom_point)-2];
			$path = "/document/".$deal_id."_".time()."_".$i.".".$extentions;
			if (upload_file($path, $document)) {
				$sql_to_execute .= "('{$deal_id}', '{$filename}', '{$path}')";
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
		$documents = dbQuery("SELECT title, path FROM deal_document WHERE deal_id = '{$deal_id}'");
		$category = dbQueryOne("SELECT category.* FROM deal_category, category WHERE deal_category.deal_id = '{$deal_id}' AND category.id = deal_category.category_id");
		send_answer([
			"deal" => $deal,
			"documents" => $documents,
			"category" => $category
		], true); 
	}
	send_answer(["Дело с указанным ID не найдено"]);
}

function get_category_all() {
	$categorys = dbQuery("SELECT * FROM category");
	send_answer(["categorys" => $categorys], true);
}

function get_account(){
	global $currentUser;

	$currentUser["active_deals"] = 0;
	$currentUser["unactive_deals"] = 0;
	$currentUser["all_deals"] = 0;

	$deals = dbQuery("SELECT * FROM deal WHERE account_id = '{$currentUser['id']}'");
	for($i = 0; $i < count($deals); $i++){ 
		if($deals[$i]['status'] == "active"){ 
			$currentUser["active_deals"]++;
		} else {
			$currentUser["unactive_deals"]++;
		}
		$currentUser["all_deals"]++;
	}

	send_answer(["account" => $currentUser], true);
}

function add_document(){
	global $currentOptions, $currentUser;
	$deal_id = $currentOptions['id'];
	if ($deal = dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}' AND account_id = '{$currentUser['id']}'")) {
		$documents = [];
		for ($i = 0; $i < 20; $i++) {
			if (isset($_FILES['document'.$i]) && $_FILES['document'.$i]["name"] != null && isset($currentOptions['document_name'.$i])) {
				$documents[] = [$_FILES['document'.$i], $currentOptions['document_name'.$i]];
			} else {
				break;
			}
		}
		$warrings = [];
		if ($documents != []) {
			$sql_to_execute = "INSERT INTO deal_document (deal_id, title, path) VALUES ";
			$i= 0;
			foreach ($documents as $document) {
				$extentions = array_slice(explode(".", $document[0]['name']), -1)[0];
				$path = "/document/".time()."_".$i.".".$extentions;
				if (upload_file($path, $document[0])) {
					$sql_to_execute .= "('{$deal_id}', '{$document[1]}', '{$path}')";
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
	send_answer(["Дело с указанным ID не найдено или оно Вам не принадлежит"]);
}

function remove_document(){
	global $currentOptions, $currentUser;
	$document_id = $currentOptions['id'];
	if (!dbQueryOne("SELECT deal.id FROM deal, deal_document WHERE deal_document.id = '{$document_id}' AND deal.id = deal_document.deal_id AND deal.account_id = '{$currentUser['id']}'")) {
		send_answer(["Связанное дело Вам не принадлежит"]);
	}
	if(!dbExecute("DELETE FROM deal_document WHERE id = '{$document_id}'")){
		send_answer(["Документ не был откреплён"]);
	}
	send_answer([], true);
}

function end_deal(){
	global $currentOptions, $currentUser;
	$deal_id = $currentOptions['id'];
	if(!dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}' AND account_id = '{$currentUser['id']}'")){
		send_answer(["Дело Вам не принадлежит"]);
	}
	if(!dbExecute("UPDATE deal SET status = 'unactive' WHERE id = '{$deal_id}'")){
		send_answer(["Неизвестная ошибка закрытия дела"]);
	}
	send_answer([], true);
}

function edit_deal(){
	global $currentOptions, $currentUser;
	$deal_id = $currentOptions['id'];

	if(!dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}' AND account_id = '{$currentUser['id']}'")){
		send_answer(["Дело Вам не принадлежит"]);
	}

	$fullname = verify_field("ФИО", $currentOptions['fullname'], 3, 600);
	$date_born = verify_field("Дата рождения", $currentOptions['date_born'], 2, 45);
	$passport_series = verify_field("Серия паспорта", $currentOptions['passport_series'], 1, 11);
	$passport_id = verify_field("Номер паспорта", $currentOptions['passport_id'], 1, 11);
	$passport_issued = verify_field("Кем выдан паспорт", $currentOptions['passport_issued'], 1, 120);
	$passport_date = verify_field("Дата выдачи", $currentOptions['passport_date'], 1, 45);
	$short_text = verify_field("Краткое описание дела", $currentOptions['short_text'], 1, 45);
	$category_id = verify_field("Категория", $currentOptions['category'], 1, 11);

	if(!dbExecute("UPDATE deal SET fullname = '{$fullname}', date_born = '{$date_born}', passport_series = '{$passport_series}', passport_id = '{$passport_id}', passport_issued = '{$passport_issued}', passport_date = '{$passport_date}', short_text = '{$short_text}' WHERE id = '{$deal_id}'")){
		send_answer(["Неизвестная ошибка обновления дела"]);
	}

	if(!dbExecute("UPDATE deal_category SET category_id = '{$category_id}' WHERE deal_id = '{$deal_id}'")){
		send_answer(["Неизвестная ошибка обновления категории дела"]);
	}

	send_answer([], true);
}