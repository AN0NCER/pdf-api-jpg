<?php
$log_file = 'log.txt';

// Запись логов в файл
function write_log($log_file, $message) {
    $time = date("Y-m-d H:i:s");
    $log_string = "[" . $time . "]" . $message;
    file_put_contents($log_file, $log_string . PHP_EOL, FILE_APPEND);
}

write_log($log_file, "[SCRIPT]: A request came to the server");

$_pdf = "temp.pdf";
$_folder = "images/";

if (!isset($_GET['url'])) {  
    if(isset($_GET['clear'])){
        ClearCache($_folder);
        CheckFile($_pdf);
    }else{
        http_response_code(404);
    }
    write_log($log_file, "[ERROR]: Not found 'url'");
    return;
}

header('Content-Type: application/json; charset=utf-8');

CheckFolder($_folder);
CheckFile($_pdf);

file_put_contents($_pdf, fopen($_GET['url'], 'r'));

$imagick = new Imagick();

/*if (isset($_GET['format'])) {
    $imagick->setImageFormat($_GET['format']);
} else {
    $imagick->setImageFormat('jpeg');
}*/

if (isset($_GET['w']) || isset($_GET['h'])) {
    $imagick->setResolution($_GET['w'], $_GET['h']);
} else {
    $imagick->setResolution(200, 200);
}

$imagick->readImage($_pdf);
$pages = $imagick->getNumberImages();
$success = $imagick->writeImages($_folder . '/myimage.jpg', false);

if ($success) {
    // Вызов записи лога об успешном выполнении скрипта
    write_log($log_file, "[SUCCESS]: Script executed successfully");
} else {
    // Вызов записи лога об ошибке
    write_log($log_file, "[ERROR]: Error writing image to file");
}

$return = array();

if ($pages > 1) {
    for ($i = 0; $i < $pages; $i++) {
        array_push($return, "https://" .  $_SERVER['SERVER_NAME'] . "/imagick/" . $_folder . 'myimage-' . $i . '.jpg');
    }
} else {
    array_push($return, "https://" . $_SERVER['SERVER_NAME'] . "/imagick/" .  $_folder . 'myimage.jpg');
}

echo (json_encode($return));

function CheckFolder($folder)
{
    if (!is_dir($folder)) {
        mkdir($folder);
    }
}

function CheckFile($file)
{
    if (file_exists($file)) {
        unlink($file);
    }
}

function ClearCache($dir_images)
{
    $dir = $dir_images;
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator(
        $it,
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}