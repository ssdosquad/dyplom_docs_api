<?php
// Подключаем автозагрузку классов
require_once ROOTDIR."/vendor/autoload.php";
// Подключаем нужные классы
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\PhpWord;

function ready_template_download($options, $template){
	// Убеждаемся, имеет ли composer нужные зависимости
	// Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
	// Указываем директорию загрузки как текущую
	// Settings::setPdfRendererPath('.');
	// Создаём класс для работы с .docx файлами
	$phpWord = new PhpWord();
	// Загружаем шаблон
	$template_path = ROOTDIR."/template/".$template.".docx";
	$document = $phpWord->loadTemplate($template_path);
	// Подставляем все параметры в документ
	foreach($options as $key => $value){
		$document->setValue($key, $value);
	}
	// Сохраняем файл во временную директорию для моментальной загрузки
	$temp_word_file = ROOTDIR."/tmp/".time()."_word.docx";
	$document->save($temp_word_file);
	// Открываем временный файл для конвертации в PDF
	// $convertWord = IOFactory::load($temp_file, 'Word2007');
	// Конвертируем в PDF
	// $convertWord->save($temp_file, 'PDF');
	// Указываем заголовки ответа для загрузки файла, имя - document.pdf 
	// header("Content-type:application/pdf");
	header("Content-type:application/msword");
	// header("Content-Disposition: attachment; filename=document.pdf");
	header("Content-Disposition: attachment; filename=document.docx");
	readfile($temp_word_file); // выводим содержимое файла для загрузки
	// unlink($temp_word_file);  // удаляем временный файл после загрузки
}