<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config_dvr.php';


function conf()
{
    $path = parse_json_config('private/.path.json');
    $http_root_path = $path['http_root_path']; // Внутренний путь к файлам
    $absolute_root_path = $path['absolute_root_path']; // Абсолютный пусть к файлам

    return ['global_marks' => ['http_root' => $http_root_path,
                               'http_css' => $http_root_path.'css/',
                               'http_img' => $http_root_path.'i/',
                               'http_video' => $http_root_path.'videos/',
                               'http_js' => $http_root_path.'js/',
                               'time' => time(),
                               'qurl' => $http_root_path.'query.php'],
            'http_root_path' => $http_root_path,
            'absolute_root_path' => $absolute_root_path];
}


function conf_db()
{
    static $config = NULL;
    if (!is_array($config))
        $config = parse_json_config('private/.database.json');

    return $config;
}

?>
