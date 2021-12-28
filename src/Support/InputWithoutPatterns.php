<?php

namespace Chatman\Support;

use writecrow\Lemmatizer\Lemmatizer;

class InputWithoutPatterns{
    
    private $model;
    private $data;
    private $tokenizer;
    private $words;
    private $defaultFunction;
    private $text;

    public $stopWords;

    public function __construct(object $model,array $data, object $tokenizer, array $words,
                                callable $defaultFunction = NULL, array $stopWords){
        $this->model = $model;
        $this->data = $data;
        $this->tokenizer = $tokenizer;
        $this->words = $words;
        $this->defaultFunction = $defaultFunction;
        $this->stopWords = $stopWords;
    }

    private function cleanText(string $text) : array
    {
        $tempTokens = $this->tokenizer->tokenize($text);
        $tokens = [];

        foreach($tempTokens as $word){
            if(!in_array($word, $this->stopWords)){
                $tokens[] = Lemmatizer::getLemma(strtolower($word));
            }
           
        }
        
        return $tokens;
    }

    private function removeStopWords(array $text) : string 
    {
        $newText = [];
        foreach($text as $index => $word){
            if(!in_array($word, $this->stopWords)){
                $newText[] = $word;
            }
        }
        
        return implode(" ", $newText);

    }

    private function bagOfWords(string $text) : array
    {
        $tokens = $this->cleanText($text);

        if(empty($tokens)){
            return [];
        }

        $bow =  array_fill(0, count($this->words),0);

        foreach($tokens as $w){
            foreach($this->words as $index => $word){
                if ($word == $w){
                    $bow[$index] = 1;
                }
            }
        }


        return $bow;
    }

    private function readyResponse(string $result) : string
    {

    
        $result = explode("|", $result);
        $resultIndex = null;

        if(!empty($result) && in_array(1, $result)){

            foreach($result as $index => $val){
                
                if($val == 1){
                    $resultIndex = $index;
                    break;
                }
              
            }

            $resp = $this->data['responses'][$resultIndex];
           
            return $this->returnResp($resp);
            
        }
        else{
            return $this->returnDefault();
        }
       
    }

    public function getResponse(string $text) : string
    {
        $this->text = strtolower($text);    

        $bow = $this->bagOfWords($text, $this->words);
        
        if (!in_array(1, $bow)){
            
            return $this->returnDefault();
        }

        $result = $this->model->predict($bow);

        return $this->readyResponse($result);
    }

    private function returnResp(string $resp) : string
    {

        $match = preg_match("/(?<={).+?(?=})/",$resp, $matches);

        if($match){

            $resp = preg_replace("/{.*}/", "", $resp);

            if(is_callable($matches[0])){
                $cleanText = $this->removeStopWords($this->cleanText($this->text));
                call_user_func_array($matches[0], [$this->text, $cleanText, $resp]);
            }

            return $resp;

        }else{
            return $resp;
        }

    }

    private function returnDefault() : string
    {

        $this->setLastMatchedTag("_default_");

        if (is_callable($this->defaultFunction)) {
            call_user_func($this->defaultFunction, $this->text);
        }

        return "";

    }

    private function setLastMatchedTag(string $tag) : void 
    {
        $_SESSION["__Chatman__"]["lastmatchedtag"] = $tag;
    }

    private function getLastMatchedTag() : string
    {
        if(!empty($_SESSION["__Chatman__"]["lastmatchedtag"])){
            return $_SESSION["__Chatman__"]["lastmatchedtag"];
        }else{
            return "";
        }
    }

    
}
?>