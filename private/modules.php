<?php

require_once "private/mod_videos.php";

class Module {
    public $name = "undefined";

    function content($args = []) {
        return "No module content";
    }

    function query($args)
    {
        $reason = sprintf("module '%s' is not supported queries", $this->name);
        message_box_set("message_error", ['reason' => $reason]);
        return mk_url();
    }
}

class Modules {
    function register($name, $module)
    {
        $this->modules_list[$name] = $module;
        $module->name = $name;
    }

    function mod_content($mod_name, $args)
    {
        if (!isset($this->modules_list[$mod_name]))
            return sprintf("Module '%s' not found", $mod_name);

        return $this->modules_list[$mod_name]->content($args);
    }

    function mod($mod_name)
    {
        if (!isset($this->modules_list[$mod_name]))
            return NULL;

        return $this->modules_list[$mod_name];
    }
}


function modules()
{
    static $modules = NULL;
    if (!$modules)
        $modules = new Modules;
    return $modules;
}

