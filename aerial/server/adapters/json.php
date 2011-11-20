<?php

    import('aerialframework.core.ConfigXml');
    import('aerialframework.core.Bootstrapper');
    import('aerialframework.core.AerialStartupManager');

    Bootstrapper::getInstance();

    global $args;

    set_exception_handler("exceptionHandler");

    validateArguments($args);

    $service = $args[0];
    $method = $args[1];
    $data = file_get_contents('php://input') ? file_get_contents('php://input') : @$_GET["data"];
    $data = parseRequest(json_decode($data, true));

    $servicesPath = ConfigXml::getInstance()->servicesPath;

    require_once($servicesPath."/$service.php");

    $serviceClass = new $service;
    $response = call_user_func_array(array($serviceClass, $method), $data);

    echo json_encode(simplifyResponse($response));
//    die();

//    if(is_subclass_of($bob, "Doctrine_Collection") || is_a($bob, "Doctrine_Collection"))
//    {
//        echo json_encode($bob->toArray());
//    }

    function simplifyResponse($data)
    {
        // integer, float, string or boolean
        if(is_scalar($data))
            return $data;

        if(is_array($data))
        {
            if(count($data) > 0)
            {
                foreach($data as $key => $value)
                {
                    // special object handling
                    $data[$key] = simplifyResponse($value);
                }
            }
        }

        if(is_object($data))
        {
            // special handling for Doctrine classes
            if(is_subclass_of($data, "Doctrine_Collection") || is_a($data, "Doctrine_Collection")
                    || is_subclass_of($data, "Doctrine_Record") || is_a($data, "Doctrine_Record"))
            {
                $data = $data->toArray();
            }
            else
            {
                foreach($data as $key => $value)
                {
                    $data->$key = simplifyResponse($value);
                }
            }
        }

        return $data;
    }

    function parseRequest($data)
    {
        if(is_scalar($data))
        {
            if($data === "__undefined__")
                $data = new undefined();

            return $data;
        }

        if(is_array($data))
        {
            if(count($data) > 0)
            {
                foreach($data as $key => $value)
                {
                    $data[$key] = parseRequest($value);
                }
            }
        }

        if(is_object($data))
        {
            foreach($data as $key => $value)
            {
                $data->$key = parseRequest($value);
            }
        }

        return $data;
    }

    function validateArguments($args)
    {
        if (!checkForValidArguments($args))
            throw new Exception("Invalid (null) arguments: " . print_r($args, true));

        if (!$args || count($args) <= 2) // need at least 2 parameters (class, method)
        {
            if (count($args) < 2)
                throw new Exception("No method name passed in arguments: " . print_r($args, true));

            if (count($args) < 1)
                throw new Exception("No service name passed in arguments: " . print_r($args, true));
        }
    }

    function checkForValidArguments($args)
    {
        if (!$args || count($args) <= 0)
            return false;

        foreach ($args as $argument)
        {
            if (strlen(trim($argument)) == 0)
                return false;
        }

        return true;
    }

    function is_undefined($obj)
    {
        return is_object($obj) ? get_class($obj) == "undefined" : false;
    }

    function exceptionHandler(Exception $e)
	{
		die(json_encode(array("exception" => true,
                                "line" => $e->getLine(),
                                "file" => $e->getFile(),
                                "message" => $e->getMessage())));
	}

    final class undefined
    {
        public function exists()
        {
            return false;
        }
    
        public function __toString()
        {
            return 'undefined';
        }
    }

?>