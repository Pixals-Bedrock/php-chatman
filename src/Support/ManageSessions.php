<?php

namespace Chatman\Support;

class ManageSessions{

    //session responses getter functions

    public function getLastBotResp(): string
    {
        if(!empty($_SESSION["__Chatman__"]["lastbotresp"])){
            return $_SESSION["__Chatman__"]["lastbotresp"];
        }

        return "";
    }

    public function getLastUserQuest(): string
    {
        if(!empty($_SESSION["__Chatman__"]["lastuserquest"])){
            return $_SESSION["__Chatman__"]["lastuserquest"];
        }

        return "";
    }

    public function getLastMatchedTag(): string
    {
        if(!empty($_SESSION["__Chatman__"]["lastmatchedtag"])){
            return $_SESSION["__Chatman__"]["lastmatchedtag"];
        }

        return "";
    }

    public function destroySession(string $name = "") : void{

        if(!empty($name)){

            if(isset($_SESSION["__Chatman__"][$name])){
                unset($_SESSION["__Chatman__"][$name]);
            } 
        }
        else{

            if(isset($_SESSION["__Chatman__"])){
                unset($_SESSION["__Chatman__"]);
            }
        }
       
    }

    //reset time in seconds
    public function autoDestroy(int $resetTime) : void
    {

        if(!isset($_SESSION["__Chatman__"]["time"])){
            $_SESSION["__Chatman__"]["time"] = time();
            return;
        }

        if( time() - $_SESSION["__Chatman__"]["time"] >= $resetTime){
            unset($_SESSION["__Chatman__"]);
            return;
        }

        $_SESSION["__Chatman__"]["time"] = time();
    }

}



?>