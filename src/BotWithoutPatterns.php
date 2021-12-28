<?php

namespace Chatman;

use Phpml\ModelManager;
use Phpml\Tokenization\WordTokenizer;
use Phpml\Classification\NaiveBayes;
use writecrow\Lemmatizer\Lemmatizer;
use Chatman\Support\InputWithoutPatterns;
use Exception;

class BotWithoutPatterns
{

    //use json data
    public $data = [];

    private $words = [];
    private $classes = [];
    private $X = [];
    private $y = [];
    private $tokenizer;
    private $train_X;
    private $train_y;
    private $model;
    private $modelManager;
    private $botFinalResponse;
    private $defaultFunction = NULL;

    public $isFinalResponse = false;

    //stop words & punctuation
    public $stopWords = [
        'all', 'those', 'i', "would", 'was', "didn't", '\\', "she's", 'hasn', "mustn't", 'weren', "won't", 'yours', 'itself', 's', "haven't", 'above', '@', 'her', 'isn', '<', 'off', "don't", '*', 'which', 'doesn', 'mustn', 'should', ':', "isn't", 'such', 'myself', 'while', 'any', "you'd", 'because', 'down', 'am', 'can', 'no', 'did', 'theirs', 'again', '}', 'here', ')', 'most', 'been', 'in', 'under', 'him', "weren't", 'few', 'themselves', 'y', 'doing', 'until', 'ma', 'does', 'each', 'd', 'haven', 'not', '=', ',', '^', 'couldn', 'll', "mightn't", '!', 'this', 'only', "should've", 'being', "shouldn't", ';', 'be', 'between', 'with', 'm', 'yourselves', 'of', 'had', 'too', 'or', 'during', 'same', 'its', 'ours', 'before', '~', 'there', ']', '{', 'after', 'hadn', 'shan', 'on', 'having', 'we', 'whom', 'they', 'our', 'if', 'mightn', '+', 'i', 'for', 'some', "you'll", 'why', 'but', 're', 'then', 'nor', '#', 'hers', 'ain', '|', 'so', 'just', 'it', 'up', 'wasn', 'where', '(', 'herself', 'when', 'other', 'than', 'have', 'will', '-', 'me', '.', "hadn't", 'against', '"', 'didn', 'shouldn', "needn't", '[', 'has', "couldn't", 'at', 't', "you've", 'a', 'o', 'as', 'an', '%', '&', 'my', 'don', 'them', 'your', "wasn't", 'won', 've', 'who', 'very', 'wouldn', 'to', 'how', 'himself', 'that', 'and', 'yourself', 'out', 'you', 'what', 'into', 'he', 'from', 'once', '?', "doesn't", 'ourselves', 'by', 'more', 'aren', '$', "wouldn't", 'about', '`', 'own', 'through', 'were', "you're", 'these', 'over', '/', '>', 'his', 'now', 'do', "hasn't", 'are', "it's", 'the', 'needn', "'", 'their', "shan't", "aren't", 'she', '_', "that'll", 'below', 'is', 'both', 'further'
    ];

    //it will be return when chatman cannot predict
    public $defaultMsg = "I didn't understand !";

    //opening json, setting required attributes
    //starting session to save responses
    function __construct(string $jsonFile)
    {
        $file = file_get_contents($jsonFile);
        $this->data = json_decode($file, true);
        $this->tokenizer = new WordTokenizer();
        $this->modelManager = new ModelManager();

        session_start();
    }

    //calling all required private methods
    public function train() : void
    {
        $this->NL();
        $this->trainModel();
    }

    //getting data ready for training.
    //tokenizing, stemming, removing stopwords
    //making bag of word for output
    private function NL(): void
    {

        foreach ($this->data['responses'] as $index => $resp) {

            $tokens = $this->tokenizer->tokenize($resp);
            $this->words = array_merge($this->words, $tokens);
            $this->X[] = strtolower($resp);
            $this->y[] = strtolower($index);

            if (!in_array($index, $this->classes)) {
                $this->classes[] = $index;
            }
        }

        $tempWords = $this->words;
        $this->words = [];

        foreach ($tempWords as $word) {
            if (!in_array($word, $this->stopWords)) {
                $this->words[] = Lemmatizer::getLemma(strtolower($word));
            }
        }

        $training = [];

        $output = [];

        foreach ($this->X as $index => $doc) {

            //bag of words
            $bow = [];

            $text = Lemmatizer::getLemma($doc);

            foreach ($this->words as $word) {

                if (strpos($text, $word) !== false) {
                    $bow[] = 1;
                } else {
                    $bow[] = 0;
                }
            }

            $output = array_fill(0, count($this->classes), 0);
            $cIndex = array_search($this->y[$index], $this->classes);
            $output[$cIndex] = 1;

            $training[] = [$bow, $output];
        }

        shuffle($training);

        $this->train_X = array_column($training, 0);

        $labels = array_column($training, 1);

        foreach ($labels as $label) {
            $this->train_y[] = implode("|", $label);
        }
    }


    //setting words
    //using when training is not required.
    private function setWords()
    {
        foreach ($this->data['responses'] as $index => $resp) {

            $tokens = $this->tokenizer->tokenize($resp);
            $this->words = array_merge($this->words, $tokens);
            $this->X[] = strtolower($resp);
            $this->y[] = strtolower($index);

            if (!in_array($index, $this->classes)) {
                $this->classes[] = $index;
            }
        }

        $tempWords = $this->words;
        $this->words = [];

        foreach ($tempWords as $word) {
            if (!in_array($word, $this->stopWords)) {
                $this->words[] = Lemmatizer::getLemma(strtolower($word));
            }
        }

    }

    //training model with php-ml NaiveBayes
    private function trainModel(): void
    {

        $this->model = new NaiveBayes();
        $this->model->train($this->train_X, $this->train_y);
    }

    //Creating instance of Input class
    //Getting predictions
    //Validating reponse
    public function getResponse(string $text): string
    {

        //if final resp set
        if (!empty($this->botFinalResponse)) {
            $this->setLastMatchedTag("_final_");
            return $this->botFinalResponse;
        }

        $data = new InputWithoutPatterns(
            $this->model,
            $this->data,
            $this->tokenizer,
            $this->words,
            $this->defaultFunction,
            $this->stopWords
        );

        $response = $data->getResponse($text);

        $this->setLastUserQuest($text);

        //again checking 
        if (!empty($this->botFinalResponse)) {
            $this->setLastMatchedTag("_final_");
            return $this->botFinalResponse;
        }

        if (empty($response)) {

            $this->setLastBotResp($this->defaultMsg);

            return $this->defaultMsg;

        } else {

            $this->setLastBotResp($response);
            return $response;
        }
    }

    //setting final bot response. 
    //Once it set, getResponse will not predict instead it will return $this->botFinalResponse
    public function finalResponse(string $resp): string
    {
        if (empty($resp)) {
            $this->isFinalResponse = false;
        } else {
            $this->isFinalResponse = true;
        }

        $this->botFinalResponse = $resp;
        $this->setLastBotResp($resp);
        $this->setLastMatchedTag("_final_");

        return $resp;
    }

    public function newResponse(string $resp): string
    {

        $this->setLastBotResp($resp);

        return $resp;
    }

    //on default response.
    //Very useful for live support
    public function onDefaultResp(callable $func): void
    {
        $this->defaultFunction = $func;
    }

    //saving trained model
    public function exportModel(string $filepath): bool
    {

        try {
            $this->modelManager->saveToFile($this->model, $filepath);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    //importing trained model
    public function importModel(string $filepath): bool
    {
        try {
            $this->model = $this->modelManager->restoreFromFile($filepath);
            $this->setWords();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function setLastBotResp(string $text): void
    {
        $_SESSION["__Chatman__"]["lastbotresp"] = $text;
    }

    private function setLastUserQuest(string $text): void
    {
        $_SESSION["__Chatman__"]["lastuserquest"] = $text;
    }
    private function setLastMatchedTag(string $tag): void
    {
        $_SESSION["__Chatman__"]["lastmatchedtag"] = $tag;
    }
}
