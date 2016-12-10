<?php

session_start();

//$method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', strtolower(substr($_SERVER['REQUEST_URI'], 1)));
$route = $uri[1];
//die($route);
//$parameter = $uri[1] ? $uri[1] : "";
//$input =  json_decode(file_get_contents('php://input'),true);
//$post =  $_POST;
//$word = $input["word"] ? $input["word"] : $_POST["word"];
$data = array();
//$data["config"] = [
//    "uri" => implode("/",$uri),
//    "route" => $route,
//    "parameter" => $parameter,
//    "method" => $method,
//    "input" => $input,
//    "post" => $post,
//    "word" => $word
//];
//
    switch ($route) {
        case "login": {
            $data = ["state" => "loggedIn"];


//            switch ($parameter) {
//                case "": {
//                    switch ($method) {
//                        case 'GET': {
//
//                            break;
//                        } case 'POST': {
//
//                        break;
//                    } default: {
//                        $data["error"] = ["code" => 405, "type" => "Method Not Allowed", "message" => "Method not allowed, try GET /word"];
//                        break;
//                    }
//                    }
//                    break;
//                }
//                default: {
//                    switch ($method) {
//                        case 'GET': {
//
//                            break;
//                        } case 'DELETE': {
//
//                        break;
//                    } default: {
//                        $data["error"] = ["code" => 405, "type" => "Method Not Allowed", "message" => "Method not allowed, try GET /word"];
//                        break;
//                    }
//                    }
//                    break;
//                }
//            }
            break;
        }
        case "report": {
            break;
        }
        case "logout": {
//            setcookie('session',md5(rand()),time()+60*60*24*30,'/');
            session_unset();
            session_destroy();
            $data = ["state" => "loggedOut"];
            break;
        }
        default: {
            $data["error"] = ["code" => 404, "type" => "Not Found", "message" => 'Route not found'];
            break;
        }
    }


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header('Content-Type: application/json');

echo json_encode($data);






//ob_start();

//// error_reporting(E_ALL);
//// ini_set("display_errors", 1);
//    $msg = '';
//
//    if (isset($_POST['login']) && !empty($_POST['username'])
//        && !empty($_POST['password'])) {
//
//        if ($_POST['username'] == 'tutorialspoint' &&
//            $_POST['password'] == '1234') {
//            $_SESSION['valid'] = true;
//            $_SESSION['timeout'] = time();
//            $_SESSION['username'] = 'tutorialspoint';
//
//            echo 'You have entered valid use name and password';
//        }else {
//            $msg = 'Wrong username or password';
//        }
//    }
//