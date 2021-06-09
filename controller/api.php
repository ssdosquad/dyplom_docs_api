<?php

function auth() {
	global $currentOptions, $session_standing;
    // Собираем данные
	$login    = verify_field("Логин", $currentOptions['login'], 0, 45);
	$password = verify_field("Пароль", $currentOptions['password'], 0, 0);
    // Ищем аккаунт с введённым логином
	if (!($query = dbQueryOne("SELECT * FROM account WHERE login = '{$login}'"))) {
		send_answer(["Аккаунт с введённым логином отсутствует"]);
	}
    // Проверяем пароль
	if (!password_verify($password, $query['password'])) {
		send_answer(["Введён неверный пароль"]);
	}
    // Собираем данные для новой сессии
	$account_id   = $query['id'];
	$session_key  = create_session($account_id);
	$session_time = time();
	$ip           = $_SERVER["REMOTE_ADDR"];
    // Записываем новую сессию
	if (!dbExecute("INSERT INTO account_session (account_id, session_key, session_time, ip) VALUES ('{$account_id}', '{$session_key}', '{$session_time}', '{$ip}')")) {
		send_answer(["Неизвестная ошибка создания новой сессии"]);
	}
    // Успех
	send_answer(["session_key" => $session_key, "session_time" => $session_standing, "account" => $query], true);
}

function get_deal_all() {
    // Получение всех дел
	$deals = dbQuery("SELECT * FROM deal ORDER BY id DESC");
	// Успех
	send_answer($deals, true);
}

function get_deal_by_category() {
	global $currentOptions;
	// Получаем ID категории
	$category_id = $currentOptions['id'];
	// Получаем список дел с категорией $category_id
	$deals = dbQuery("SELECT deal.* FROM deal, deal_category WHERE deal.id = deal_category.deal_id AND deal_category.category_id = '{$category_id}' ORDER BY id DESC");
	// Успех
	send_answer($deals, true);
}

function get_deal_active(){
    // Получаем список активных дел
	$deals = dbQuery("SELECT * FROM deal WHERE status='active' ORDER BY id DESC");
	// Успех
	send_answer($deals, true);
}

function get_deal_unactive(){
    // Получаем список неактивных дел
	$deals = dbQuery("SELECT * FROM deal WHERE status='unactive' ORDER BY id DESC");
	// Успех
	send_answer($deals, true);
}

function new_deal() {
	global $currentOptions, $currentUser;
	// Определяем ID аккаунта
	$account_id = $currentUser["id"];
	// Собираем данные
	$fullname = verify_field("ФИО", $currentOptions['fullname'], 3, 600);
	$date_born = verify_field("Дата рождения", $currentOptions['date_born'], 2, 45);
	$passport_series = verify_field("Серия паспорта", $currentOptions['passport_series'], 1, 11);
	$passport_id = verify_field("Номер паспорта", $currentOptions['passport_id'], 1, 11);
	$passport_issued = verify_field("Кем выдан паспорт", $currentOptions['passport_issued'], 1, 120);
	$passport_date = verify_field("Дата выдачи", $currentOptions['passport_date'], 1, 45);
	$short_text = verify_field("Краткое описание дела", $currentOptions['short_text'], 1, 45);
	$category_id = verify_field("Категория", $currentOptions['category'], 1, 11);
    // Собираем документы
	$documents = [];
	for ($i = 0; $i < 20; $i++) {
		if (isset($_FILES['document'.$i]) && $_FILES['document'.$i]["name"] != null) {
			$documents[] = $_FILES['document'.$i];
		} else {
			break;
		}
	}
    // Запрос на добавление дело в БД
	if (!dbExecute("INSERT INTO deal (account_id, fullname, date_born, passport_series, passport_id, passport_issued, passport_date, short_text) VALUES ('{$account_id}', '{$fullname}', '{$date_born}', '{$passport_series}', '{$passport_id}', '{$passport_issued}', '{$passport_date}', '{$short_text}')")) {
		send_answer(["Неизвестная ошибка записи нового дела в базу"]);
	}
    // Получаем ID созданного дела
	$deal_id = dbLastId();
    // Связываем новое дело с категорией
	if(!dbExecute("INSERT INTO deal_category (deal_id, category_id) VALUES ('{$deal_id}', '{$category_id}')")){
		send_answer(["Неизвестная ошибка связи дела с категорией"]);
	}
    // Определяем предупрждения
	$warrings = [];
	// Если есть документы на добавление
	if ($documents != []) {
	    // Начинаем формирование запроса
		$sql_to_execute = "INSERT INTO deal_document (deal_id, title, path) VALUES ";
		$i = 0;
		// Перебираем все документы
		foreach ($documents as $document) {
		    // Разбиваем строку по точке
			$boom_point = explode(".", $document['name']);
			// Определение расширения
			$extentions = $boom_point[count($boom_point)-1];
            // Определение имени
			$filename = $boom_point[count($boom_point)-2];
			// Определяем путь
			$path = "/document/".$deal_id."_".time()."_".$i.".".$extentions;
			// Загружаем доумент
			if (upload_file($path, $document)) {
			    // Добавляем к запросу
				$sql_to_execute .= "('{$deal_id}', '{$filename}', '{$path}')";
				if ($i < count($documents)-1) {$sql_to_execute .= ", ";
				}
			} else {
			    // Добавляем предупреждение
				$warrings[] = "Документ ({$i}) не был загружен";
			}
			$i++;
		}
		// Выполняем запрос
		if (!dbExecute($sql_to_execute)) {
		    // Добавляем предупреждение
			$warrings[] = "Документы не были прикреплены";
		}
	}
    // Успех
	send_answer($warrings, true);
}

function get_deal() {
	global $currentOptions;
	// Получаем ID дела
	$deal_id = $currentOptions['id'];
	// Получаем дело
	if ($deal = dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}'")) {
	    // Получаем связанные документы
		$documents = dbQuery("SELECT * FROM deal_document WHERE deal_id = '{$deal_id}'");
		// Получаем связанную категорию
		$category = dbQueryOne("SELECT category.* FROM deal_category, category WHERE deal_category.deal_id = '{$deal_id}' AND category.id = deal_category.category_id");
		// Успех
		send_answer([
			"deal" => $deal,
			"documents" => $documents,
			"category" => $category
		], true); 
	}
	send_answer(["Дело с указанным ID не найдено"]);
}

function get_category_all() {
    // Получаем все категории
	$categorys = dbQuery("SELECT * FROM category");
	// Успех
	send_answer(["categorys" => $categorys], true);
}

function get_account(){
	global $currentUser;
    // Определяем ко-во дел
	$currentUser["active_deals"] = 0;
	$currentUser["unactive_deals"] = 0;
	$currentUser["all_deals"] = 0;
    // Получаем все дела текущего аккаунта
	$deals = dbQuery("SELECT * FROM deal WHERE account_id = '{$currentUser['id']}'");
	for($i = 0; $i < count($deals); $i++){
	    // Считаем кол-во дел
		if($deals[$i]['status'] == "active"){ 
			$currentUser["active_deals"]++;
		} else {
			$currentUser["unactive_deals"]++;
		}
		$currentUser["all_deals"]++;
	}
    // Успех
	send_answer(["account" => $currentUser], true);
}

function add_document(){
	global $currentOptions, $currentUser;
	// Получаем ID дела
	$deal_id = $currentOptions['id'];
	// Получаем дело
	if ($deal = dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}' AND account_id = '{$currentUser['id']}'")) {
	    // Получаем документы
		$documents = [];
		for ($i = 0; $i < 20; $i++) {
			if (isset($_FILES['document'.$i]) && $_FILES['document'.$i]["name"] != null && isset($currentOptions['document_name'.$i])) {
				$documents[] = [$_FILES['document'.$i], $currentOptions['document_name'.$i]];
			} else {
				break;
			}
		}
		// Определяем контейнер для предупреждений
		$warrings = [];
		if ($documents != []) {
		    // Начинаем формирование запроса
			$sql_to_execute = "INSERT INTO deal_document (deal_id, title, path) VALUES ";
			$i= 0;
			// Перебираем все документы
			foreach ($documents as $document) {
			    // Выделяем расширение
				$extentions = array_slice(explode(".", $document[0]['name']), -1)[0];
				// Определяем путь к документу
				$path = "/document/".time()."_".$i.".".$extentions;
				// Загружаем файл
				if (upload_file($path, $document[0])) {
				    // Добавляем в запрос
					$sql_to_execute .= "('{$deal_id}', '{$document[1]}', '{$path}')";
					if ($i < count($documents)-1) {$sql_to_execute .= ", ";
					}
				} else {
				    // Выделяем предупреждение
					$warrings[] = "Документ ({$i}) не был загружен";
				}
				$i++;
			}
			// Выполяем запрос
			if (!dbExecute($sql_to_execute)) {
				$warrings[] = "Документы не были прикреплены";
			}
		}
		// Успех
		send_answer($warrings, true);
	}
	send_answer(["Дело с указанным ID не найдено или оно Вам не принадлежит"]);
}

function remove_document(){
	global $currentOptions, $currentUser;
	// Получаем ID документа
	$document_id = $currentOptions['id'];
	// Проверяем, достаточно ли у нас прав
	if (!dbQueryOne("SELECT deal.id FROM deal, deal_document WHERE deal_document.id = '{$document_id}' AND deal.id = deal_document.deal_id AND deal.account_id = '{$currentUser['id']}'")) {
		send_answer(["Связанное дело Вам не принадлежит"]);
	}
	// Удаляем документ
	if(!dbExecute("DELETE FROM deal_document WHERE id = '{$document_id}'")){
		send_answer(["Документ не был откреплён"]);
	}
	// Успех
	send_answer([], true);
}

function end_deal(){
	global $currentOptions, $currentUser;
	// Получаем ID дела
	$deal_id = $currentOptions['id'];
	// Провреяем, доступно ли нам
	if(!dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}' AND account_id = '{$currentUser['id']}'")){
		send_answer(["Дело Вам не принадлежит"]);
	}
	// Закрываем дело
	if(!dbExecute("UPDATE deal SET status = 'unactive' WHERE id = '{$deal_id}'")){
		send_answer(["Неизвестная ошибка закрытия дела"]);
	}
	// Успех
	send_answer([], true);
}

function edit_deal(){
	global $currentOptions, $currentUser;
	// Получаем ID дела
	$deal_id = $currentOptions['id'];
    // Проверяем, доступно ли нам дело
	if(!dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}' AND account_id = '{$currentUser['id']}'")){
		send_answer(["Дело Вам не принадлежит"]);
	}
    // Собираем данные
	$fullname = verify_field("ФИО", $currentOptions['fullname'], 3, 600);
	$date_born = verify_field("Дата рождения", $currentOptions['date_born'], 2, 45);
	$passport_series = verify_field("Серия паспорта", $currentOptions['passport_series'], 1, 11);
	$passport_id = verify_field("Номер паспорта", $currentOptions['passport_id'], 1, 11);
	$passport_issued = verify_field("Кем выдан паспорт", $currentOptions['passport_issued'], 1, 120);
	$passport_date = verify_field("Дата выдачи", $currentOptions['passport_date'], 1, 45);
	$short_text = verify_field("Краткое описание дела", $currentOptions['short_text'], 1, 45);
	$category_id = verify_field("Категория", $currentOptions['category'], 1, 11);
    // Обновляем информацию о деле
	if(!dbExecute("UPDATE deal SET fullname = '{$fullname}', date_born = '{$date_born}', passport_series = '{$passport_series}', passport_id = '{$passport_id}', passport_issued = '{$passport_issued}', passport_date = '{$passport_date}', short_text = '{$short_text}' WHERE id = '{$deal_id}'")){
		send_answer(["Неизвестная ошибка обновления дела"]);
	}
    // Связываем дело с категорией
	if(!dbExecute("UPDATE deal_category SET category_id = '{$category_id}' WHERE deal_id = '{$deal_id}'")){
	    // Ошибка
		send_answer(["Неизвестная ошибка обновления категории дела"]);
	}
    // Успех
	send_answer([], true);
}

function get_deal_template(){
	global $currentOptions, $currentUser;
    // Собираем данные
	$deal_id = $currentOptions['id'];
	$template_name = $currentOptions['template'];
	// Получаем дело по ID
	$deal = dbQueryOne("SELECT * FROM deal WHERE id = '{$deal_id}' AND account_id = '{$currentUser['id']}'");
    // Если дело не получено
	if(!$deal){
		send_answer(["Дело Вам не принадлежит"]);
	}
    // Формируем документ
	ready_template_download($deal, $template_name);
}

function get_account_all(){
    // Выполняем запрос на получение
    $query = dbQuery("SELECT id, typeAccount, fullname, position, departament, login FROM account");
    // Возвращаем результат
    send_answer(["accounts" => $query]);
}

function create_account(){
    global $currentOptions, $currentUser;
    // Собираем данные
    $typeAccount = $currentOptions['type'];
    $fullname = verify_field("Имя", $currentOptions['fullname'], 2, 300);
    $position = verify_field("Должность", $currentOptions['position'], 2, 120);
    $departament = verify_field("Отдел", $currentOptions['departament'], 2, 120);
    $login = verify_field("Логин", $currentOptions['login'], 1, 45);
    $password = password_hash(verify_field("Имя", $currentOptions['password'], 6, 0), PASSWORD_DEFAULT);
    // Проверка типа (если не админ - ошибка)
    if($currentUser['typeAccount'] != "admin") send_answer(["Ошибка доступа"]);
    // Выполняем запрос на создание
    if(!dbExecute("INSERT INTO account (typeAccount, fullname, position, departament, login, password) VALUES ('{$typeAccount}', '{$fullname}', '{$position}', '{$departament}', '{$login}', '{$password}')")){
        // Если запрос прошёл с ошибкой
        send_answer(["Неизвестная ошибка записи в БД"]);
    }
    // Если всё успешно
    send_answer([], true);
}

function removeAccount(){
    global $currentOptions, $currentUser;
    // Собираем данные
    $account_id = $currentOptions['id'];
    // Проверка типа (если не админ - ошибка)
    if($currentUser['typeAccount'] != "admin") send_answer(["Ошибка доступа"]);
    // Выполняем запрос на создание
    if(!dbExecute("DELETE FROM account WHERE id = '{$account_id}'")){
        // Если запрос прошёл с ошибкой
        send_answer(["Неизвестная ошибка записи в БД"]);
    }
    // Если всё успешно
    send_answer([], true);
}