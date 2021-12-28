<?php


namespace Chatman\Support;

use Chatman\BotWithPatterns;
use Chatman\BotWithoutPatterns;

class Test{

    private static $location;

    private static function testingHTML(){


        $html = file_get_contents("vendor/yousuf/chatman/data/testhtml.html");
        $html = str_replace("{{location}}", self::$location, $html);
        return $html;
    }

    public static function BotWithPatterns(string $location, string $jsonFile): string
    {
        self::$location = $location;

        if ($_SERVER['REQUEST_METHOD'] == "POST") {

            $_POST = json_decode(file_get_contents("php://input"),true);
        
            if(empty($_POST['query'])){
                exit();
            }
        
            $c = new BotWithPatterns($jsonFile);
            $c->train();

            $userText = $_POST['query'];
        
            ['tag' => $tag , 'resp' => $resp] = $c->getResponse($userText);
        
            return $resp;
        
        }
        else{

            return self::testingHTML();
        }

    }

    public static function BotWithoutPatterns(string $location, string $jsonFile): string
    {
        self::$location = $location;

        if ($_SERVER['REQUEST_METHOD'] == "POST") {

            $_POST = json_decode(file_get_contents("php://input"),true);
        
            if(empty($_POST['query'])){
                exit();
            }
        
            $c = new BotWithoutPatterns($jsonFile);
            $c->train();

            $userText = $_POST['query'];
        
            $resp = $c->getResponse($userText);
        
            return $resp;
        
        }
        else{

            return self::testingHTML();
        }

    }


   

}



?>