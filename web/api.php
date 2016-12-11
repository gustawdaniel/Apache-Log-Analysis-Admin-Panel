<?php

require_once __DIR__."/../vendor/autoload.php";
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parse(file_get_contents(__DIR__.'/../config/parameters.yml'));

session_start();

$uri = explode('/', strtolower(substr($_SERVER['REQUEST_URI'], 1)));
$route = isset($uri[1]) ? $uri[1] : "";
$parameter = isset($uri[2]) ? $uri[2] : "";

$data = array();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");
header('Content-Type: application/json');

function returnError($code,$type,$message){
    $data["error"] = ["code" => $code, "type" => $type, "message" => $message];
    echo json_encode($data);
    die();
}

    switch ($route) {
        case "login": {

            if(!isset($_POST["user"]) || !isset($_POST["pass"])) {
                returnError(400,"Bad Request","Invalid form");
            } elseif($_POST["user"]!=$config["security"]["user"] || $_POST["pass"]!=$config["security"]["pass"]) {
                returnError(403,"Forbidden","Incorrect Login or Password");
            }

            $_SESSION['user'] = $config["security"]["user"];
            $data = ["state" => "loggedIn"];

        }
        case "report": {

            if(
                (!isset($_SESSION['user'])
                    ||!$_SESSION['user'])
                &&(!isset($_SERVER["HTTP_AUTHORIZATION"])
                    ||$_SERVER["HTTP_AUTHORIZATION"]!=$config["security"]["authorization"]
                )
            ){
                returnError(403,"Forbidden","Incorrect Login or Password");
            }
            $data["report"] = [];

            $list = json_decode(file_get_contents(__DIR__ . "/../" . $config["config"]["report"] . "/list.json"));

            foreach ($list->list as $key => $value) {
                $data["report"][] = ["name" => $value, "key" => $key, "link" => "api.php/report/" . $value];
            };

            if ($parameter != "") {
                if (preg_match('/text\/html/', $_SERVER["HTTP_ACCEPT"])) {
                    header('Content-Type: text/html');
                    echo file_get_contents(__DIR__ . "/../" . $config["config"]["report"] . "/html/" . $parameter . ".html");
                } elseif (preg_match('/application\/json|\*\/\*/', $_SERVER["HTTP_ACCEPT"])) {
                    header('Content-Type: application/json');
                    echo file_get_contents(__DIR__ . "/../" . $config["config"]["report"] . "/json/" . $parameter . ".json");
                } else {
                    returnError(415,"Unsupported Media Type","Incorrect Content Type");
                }
                die();
            }
            break;
        }
        case "logout": {
            session_unset();
            session_destroy();
            $data = ["state" => "loggedOut"];
            break;
        }
        default: {
            returnError(404,"Not Found","Use route /report with Authorization header");
            break;
        }
    }


echo json_encode($data);


// error_reporting(E_ALL);
// ini_set("display_errors", 1);