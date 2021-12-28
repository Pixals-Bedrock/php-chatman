<?php

namespace Chatman\Support;

use writecrow\Lemmatizer\Lemmatizer;

class InputWithPatterns{
    
    private $model;
    private $data;
    private $tokenizer;
    private $words;
    private $ignoreMLForSmallSentences;
    private $defaultFunction;
    private $text;

    public $stopWords;

    public function __construct(object $model,array $data, object $tokenizer, array $words,
                                bool $ignoreMLForSmallSentences, callable $defaultFunction = NULL, array $stopWords){
        $this->model = $model;
        $this->data = $data;
        $this->tokenizer = $tokenizer;
        $this->words = $words;
        $this->ignoreMLForSmallSentences = $ignoreMLForSmallSentences;
        $this->defaultFunction = $defaultFunction;
        $this->stopWords = $stopWords;
    }

    private function cleanText(string $text) : array
    {
        $tempTokens = $this->tokenizer->tokenize($text);
        $tokens = [];

        foreach($tempTokens as $index => $word){
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

        //if all words removed as stop words
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

    private function readyResponse(string $result) : array
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

            $tempData = $this->data['intents'][$resultIndex];
            $resp = $tempData['responses'];
            $random =  array_rand($resp);
            $resp = $resp[$random];

            return $this->returnResp($tempData, $resp);
            
        }
        else{
            return $this->returnDefault();
        }
       
    }

    public function getResponse(string $text) : array
    {
        $this->text = strtolower($text);
        //if sentence is less than or equal to 3 words like who are you?
        

        if($this->ignoreMLForSmallSentences === true && str_word_count($text) <= 3){

            $text = preg_replace("/(\s\s+)|([^\w\s]|_)/", "",strtolower($text));

            foreach($this->data['intents'] as $data){

                foreach($data['patterns'] as $index => $pattern){

                    $pattern = preg_replace("/(\s\s+)|([^\w\s]|_)/", "",strtolower($pattern));
                    
                    if(str_word_count($pattern) <= 3 && strpos($pattern, $text) !== false) {
                        $resp = $data['responses'];
                        $random =  array_rand($resp);
                        $resp = $resp[$random];

                        return  $this->returnResp($data, $resp);
                    }
                
                }
            }
        }

        $bow = $this->bagOfWords($text, $this->words);
        
        if (!in_array(1, $bow)){
            
            return $this->returnDefault();
        }

        $result = $this->model->predict($bow);

        return $this->readyResponse($result);
    }

    private function returnResp(array $data, string $resp) : array
    {

        $cleanText = $this->removeStopWords($this->cleanText($this->text));

        //checking if intent has a linked tag
        if(isset($data['linkedTag'])){

            if($this->getLastMatchedTag() === $data['linkedTag']){

                if(!empty($data['function']) && is_callable($data['function'])){
                    call_user_func_array($data['function'], [$this->text, $cleanText, $data['tag'], $resp]);
                }

                $this->setLastMatchedTag($data['tag']);

                return [
                    "tag" => $data['tag'],
                    "resp" => $resp
                ];
            }
            else{

                return $this->returnDefault();
            }

        }
        else{

            if(!empty($data['function']) && is_callable($data['function'])){
                call_user_func_array($data['function'], [$this->text, $cleanText, $data['tag'], $resp]);
            }

            $this->setLastMatchedTag($data['tag']);

            return [
                "tag" => $data['tag'],
                "resp" => $resp
            ];

        }      

    }

    private function returnDefault() : array
    {

        $this->setLastMatchedTag("_default_");

        if (is_callable($this->defaultFunction)) {
            call_user_func($this->defaultFunction, $this->text);
        }

        return [
            "tag" => "_default_",
            "resp" => ""
        ];

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