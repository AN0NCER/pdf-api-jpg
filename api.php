<?php

$_pdf = "temp.pdf";
$_folder = "images/";

if (!isset($_GET['url'])) {  
    if(isset($_GET['clear'])){
        ClearCache($_folder);
        CheckFile($_pdf);
    }else{
        http_response_code(404);
    }
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
$imagick->writeImages($_folder . '/myimage.jpg', false);

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