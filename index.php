<?php

require_once '/usr/local/lib/php/strontium_tpl.php';
require_once '/usr/local/lib/php/database.php';
require_once '/usr/local/lib/php/common.php';

require_once "private/config.php";
require_once "private/message_box.php";
require_once "private/url.php";
require_once "private/modules.php";

function main($tpl)
{
    session_start();

    $tpl->assign(NULL);

    $mbx = message_box_get();
    if($mbx)
        $tpl->assign($mbx['block'], $mbx['data']);
    $tpl->assign('user_logout', ['link_logout' => mk_url(['method' => 'user_logout'], 'query')]);

    $mod_name = "videos";
    if(isset($_GET['mod']))
       $mod_name = $_GET['mod'];

    $content = modules()->mod_content($mod_name, $_GET);
    $tpl->assign('module', ['content' => $content]);

}

$tpl = new strontium_tpl("private/tpl/skeleton.html", conf()['global_marks'], false);
main($tpl);
echo $tpl->result();
header('Cache-Control: no-store');

