<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Connection: Keep-Alive");
header("Keep-Alive: timeout=100, max=1000");

$log_file = 'log.txt';
$global_path = '/';

//Проверяем что запрос имеет тип 'POST'
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uniqueId = uniqid(); //Уникальный id запроса

    $uploadDirectory = 'uploads/'; //Директория для загрухки PDF файлов
    mkdir($uploadDirectory);


    write_log($log_file, 'POST request. ID: ' . $uniqueId);
    if (isset($_SERVER['HTTP_REFERER'])) {
        write_log($log_file, 'Domain: ' . $_SERVER['HTTP_REFERER']);
    }
    $pdfUrl = $_POST['pdf_url'];
    if (!isset($pdfUrl)) {
        write_log($log_file, 'Empty request ["pdf_url"][400]', 'REQUEST');
        http_response_code(400);
        return;
    }

    $pdfFilePath = $uploadDirectory . $uniqueId . '.pdf';

    try {
        file_put_contents($pdfFilePath, file_get_contents($pdfUrl));
    } catch (\Throwable $th) {
        write_log($log_file, $th, 'ERROR');
        return;
    }

    write_log($log_file, 'Dwonloaded file: ' . $pdfUrl . ' -> ' . $pdfFilePath);

    try {
        $imagick = new Imagick();
        $imagick->setResolution(200, 200);
        $imagick->readImage($pdfFilePath);
        $pages = $imagick->getNumberImages();
    } catch (Throwable $th) {
        write_log($log_file, $th, 'ERROR');
        unlink($pdfFilePath);
        http_response_code(500);
        return;
    }

    mkdir($uniqueId);

    $success = $imagick->writeImages($uniqueId . '/' . $uniqueId . '.jpg', false);

    unlink($pdfFilePath);
    write_log($log_file, "Remove pdf file " . $pdfFilePath);

    if ($success) {
        write_log($log_file, "Success writing images (" . $pages . ")");
    } else {
        // Вызов записи лога об ошибке
        write_log($log_file, "Error writing image to file", 'ERROR');
        http_response_code(405);
        return;
    }

    $files = array();

    if ($pages > 1) {
        for ($i = 0; $i < $pages; $i++) {
            $file = "https://" .  $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $global_path . GeneratePHP($uniqueId . '-' . $i, $uniqueId);
            array_push($files, $file);
            write_log($log_file, 'Generated PHP file: ' . $file);
        }
    } else {
        $file = "https://" .  $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $global_path . GeneratePHP($uniqueId, $uniqueId);
        array_push($files, $file);
        write_log($log_file, 'Generated PHP file: ' . $file);
    }

    $json_data = array('id'=> $uniqueId, 'files' => $files);
    $json = json_encode($json_data);
    write_log($log_file, 'Generated json response: '.$json);
    echo $json;
} else {
    write_log($log_file, 'Not allowed method (' . $_SERVER['REQUEST_METHOD'] . '). From ' . $_SERVER['REMOTE_ADDR'] . ' [405]', 'REQUEST');
    http_response_code(405);
    return;
}

// Запись логов в файл
function write_log($log_file, $message, $type = 'OK')
{
    $time = date("Y-m-d H:i:s");
    $log_string = "[" . $time . "][" . $type . "] " . $message;
    file_put_contents($log_file, $log_string . PHP_EOL, FILE_APPEND);
}

//Генерация php файла для изображение 1 раза просмотра
function GeneratePHP($imageid, $uniqueId)
{
    $file = $imageid . '.php';
    $content = "<?php header('Content-Type: image/jpeg'); header('Access-Control-Allow-Origin: *'); readfile('" . $imageid . ".jpg'); unlink('" . $imageid . ".jpg'); unlink('" . $file . "');if(!glob('" . $uniqueId . "/*')){rmdir('../" . $uniqueId . "');}";
    file_put_contents($uniqueId . '/' . $file, $content);
    return $uniqueId . '/' . $file;
}