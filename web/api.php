<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);

require_once __DIR__."/../vendor/autoload.php";
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parse(file_get_contents(__DIR__.'/../config/parameters.yml'));

session_start();

$uri = explode('/', strtolower(substr($_SERVER['REQUEST_URI'], 1)));
$route = $uri[1];
$parameter = isset($uri[2]) ? $uri[2] : "";

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

//            echo var_dump($_SERVER);
//            die($_SERVER["HTTP_AUTHORIZATION"]);

            if(
                (!isset($_SESSION['user'])
                    ||!$_SESSION['user'])
                &&(!isset($_SERVER["HTTP_AUTHORIZATION"])
                    ||$_SERVER["HTTP_AUTHORIZATION"]!=$config["security"]["authorization"]
                )
            ){
                $data["error"] = ["code" => 403, "type" => "Forbidden", "message" => 'Incorrect Login or Password'];
                echo json_encode($data);
                die();
            }
//            elseif($_SESSION['user'] || ) {
                $data["report"] = [];

                $list = json_decode(file_get_contents(__DIR__ . "/../" . $config["config"]["report"] . "/list.json"));

                foreach ($list->list as $key => $value) {
                    $data["report"][] = ["name" => $value, "key" => $key, "link" => "api.php/report/" . $value];
                };

                if ($parameter != "") {
//                var_dump($_SERVER["HTTP_ACCEPT"]);
//                die($_SERVER["HTTP_ACCEPT"]);
                    if (preg_match('/text\/html/', $_SERVER["HTTP_ACCEPT"])) {
                        header('Content-Type: text/html');
                        echo file_get_contents(__DIR__ . "/../" . $config["config"]["report"] . "/html/" . $parameter . ".html");
                    } elseif (preg_match('/application\/json|\*\/\*/', $_SERVER["HTTP_ACCEPT"])) {
                        header('Content-Type: application/json');
                        echo file_get_contents(__DIR__ . "/../" . $config["config"]["report"] . "/json/" . $parameter . ".json");
                    } else {
                        $data["error"] = ["code" => 415, "type" => "Unsupported Media Type", "message" => 'Incorrect Content Type'];
                        echo json_encode($data);
                        die();
                    }
                }
//            }

            break;
        }
        case "logout": {
            session_unset();
            session_destroy();
            $data = ["state" => "loggedOut"];
            break;
        }
        default: {
            $data["error"] = ["code" => 404, "type" => "Not Found", "message" => 'Use route /report with Authorization header'];
            break;
        }
    }


echo json_encode($data);
