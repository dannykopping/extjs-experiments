<?php

    require_once("server.php");

    if (!isset($_GET["update"]))
    {
        $query = Doctrine_Query::create()
                ->select("u.id, u.firstName, u.lastName, u.email")
                ->from("User u")
                ->limit(100);

        $users = $query->execute();

        $return = array("data" => $users->toArray(), "success" => "true");

        echo json_encode($return);
    }
    else
    {
        $updateInfo = json_decode($GLOBALS["HTTP_RAW_POST_DATA"]);
        $user = Doctrine_Core::getTable('User')->find($updateInfo->id);

        foreach($updateInfo as $key => $value)
        {
            $user->$key = $value;
        }

        echo json_encode(array("success" => ($user->trySave() ? "true" : "false")));
    }

?>