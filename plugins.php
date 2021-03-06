<?php

require_once("modules.php");
$PLUGINS = array();

foreach (array_diff(scandir(PLUGINS_FOLDER), array('..', '.')) as $_=>$dir) {
    $interface = PLUGINS_FOLDER . "/" . $dir . "/include.json";
    if (file_exists($interface)) {
        $data = json_decode(file_get_contents($interface));
        $data->directory = $dir;

        foreach ($data->modules as $modId) {
            if (!isset($MODULES[$modId])) {
                $data->error = "The plugin requires unknown module.";
                break;
            } else if (isset($MODULES[$modId]->error)) {
                $data->error = "Dependency module is invalid: " . $MODULES[$modId]->error;
                break;
            }
        }
        $PLUGINS[$data->id] = $data;
    }
}
?>
