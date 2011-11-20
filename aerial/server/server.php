<?php

    /*
    * Set the location of config.php.
    */
    $config = realpath("../config/config.php");
    if (!file_exists($config))
        die("Error: cannot find 'config.php' on line 6 of " . __FILE__);

    include_once($config);

    $adapters = array("json", "amf");

    $selectedAdapter = null;

    $args = $_SERVER["PATH_INFO"];
    if (!$args || strlen($args) <= 1)
        $selectedAdapter = "amf";
    else
    {
        $args = substr($args, 1); // remove leading forward slash

        $args = explode("/", $args);
        if (!$args || count($args) <= 0)
            $selectedAdapter = "amf";
        else
        {
            if (in_array($args[0], $adapters))
            {
                $selectedAdapter = $args[0];
                array_shift($args);
            }
            else
                throw new Exception("Adapter \"" . $args[0] . "\" not available");
        }
    }

    switch($selectedAdapter)
    {
        case "json":
            require_once("adapters/json.php");
            break;

        case "amf":
        default:
            require_once("adapters/amf.php");
            break;
    }

?>