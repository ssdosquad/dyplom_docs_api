<?php
// Подключаем автозагрузку классов
require_once ROOTDIR."/vendor/autoload.php";
// Подключаем нужные классы
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
 * @throws \PhpOffice\PhpWord\Exception\Exception
 * @throws \Mpdf\MpdfException
 */
function ready_template_download($options, $template){
	// Убеждаемся, имеет ли composer нужные зависимости
    Settings::setPdfRendererName(Settings::PDF_RENDERER_TCPDF);
	// Указываем директорию загрузки как текущую
    Settings::setPdfRendererPath(ROOTDIR.'/vendor/tecnickcom/tcpdf');
	// Создаём класс для работы с .docx файлами
	$document = new TemplateProcessor(ROOTDIR."/template/".$template.".docx");
	// Подставляем все параметры в документ
	foreach($options as $key => $value){
		$document->setValue($key, $value);
	}
	// Сохраняем файл во временную директорию для моментальной загрузки
	$temp_word_file = ROOTDIR."/tmp/".time()."_word.docx";
	$document->saveAs($temp_word_file);
	// Открываем временный файл для конвертации в PDF
    $temp_html_file = ROOTDIR."/tmp/".time().".html";
    $temp_pdf_file = "/tmp/".time()."_pdf.pdf";
    // Загружаем созданный DOCX файл для конвертации в HTML
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($temp_word_file);
    $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord , 'HTML');
    $xmlWriter->save($temp_html_file);
    // Создаём обработчик PDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8', // кодировка (по умолчанию UTF-8)
        'format' => 'A4', // - формат документа
        'orientation' => 'P' // - альбомная ориентация
    ]);
    // Заголовок
    $mpdf->SetTitle('document');
    // Автор
    $mpdf->SetAuthor('System');
    // Контент
    $file_content = file_get_contents($temp_html_file);
    preg_match_all('/<style>(.+?)<\/style>/ms', $file_content, $style, PREG_SET_ORDER, 0);
    $mpdf->WriteHTML($style[0][1], 1);
    $mpdf->WriteHTML(str_replace([$style[0][0], "<title>PHPWord</title>"], "", $file_content), 2);

    unlink($temp_word_file);  // удаляем временный файл после загрузки
    unlink($temp_html_file);  // удаляем временный файл после загрузки

    $mpdf->Output(ROOTDIR.$temp_pdf_file); // Выводим в ответ
    return $temp_pdf_file;
}