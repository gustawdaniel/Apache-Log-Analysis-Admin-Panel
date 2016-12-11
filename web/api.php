<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);

require_once __DIR__."/../vendor/autoload.php";
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parse(file_get_contents(__DIR__.'/../config/parameters.yml'));

session_start();

//$method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', strtolower(substr($_SERVER['REQUEST_URI'], 1)));
$route = $uri[1];
//$parameter = $uri[1] ? $uri[1] : "";

$data = array();


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header('Content-Type: application/json');

    switch ($route) {
        case "login": {

            if(!isset($_POST["user"]) || !isset($_POST["pass"])) {
                $data["error"] = ["code" => 400, "type" => "Bad Request", "message" => 'Invalid form'];
                echo json_encode($data);
                die();
            } elseif($_POST["user"]!=$config["security"]["user"] || $_POST["pass"]!=$config["security"]["pass"]) {
                $data["error"] = ["code" => 403, "type" => "Forbidden", "message" => 'Incorrect Login or Password'];
                echo json_encode($data);
                die();
            }

            $_SESSION['user'] = $config["security"]["user"];
            $data = ["state" => "loggedIn"];

        }
        case "report": {

            if(!$_SESSION['user']){
                $data["error"] = ["code" => 403, "type" => "Forbidden", "message" => 'Incorrect Login or Password'];
                echo json_encode($data);
                die();
            }

            $data["report"] = [["name"=>"a"],["name"=>"b"],["name"=>"c"]];

            break;
        }
        case "logout": {
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





//$data = $config;
echo json_encode($data);






//ob_start();


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