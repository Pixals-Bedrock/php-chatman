# PHP Chatman

Chatman is a machine-learning based chatbot library build in PHP. It can generate responses by learning the conversations. Chatman is very easy to use. It can be used for generating auto responses for social media, customer support, ecommerce website, personal fun etc.

Read [Documentation](https://php-chatman.netlify.app)
<br />

First make sure your system has composer installed.

```
composer require yousuf/chatman
```
<br />

>If you get any error like this :


```
Your requirements could not be resolved to an installable set of packages.

Problem 1
- Root composer.json requires yousuf/chatman ^1.0 -> satisfiable by yousuf/chatman[v1.0.0].
- yousuf/chatman v1.0.0 requires writecrow/lemmatizer dev-master -> found writecrow/lemmatizer[dev-master] but it does not match your minimum-stability.
```
<br />

>Then run these commands : 


```
composer require writecrow/lemmatizer:dev-master
composer require yousuf/chatman
```

<br />

## Training Dataset : 

```json
{
"intents": [

{"tag": "intro",
  "patterns": ["Hello", "Hi", "Hi there", "hey", "Whats up"],
  "responses": ["Hi, how can I help you ?", "Hey, do you need any help ?", "How are you doing?", "Greetings !"]
},

{"tag": "age",
  "patterns": ["how old are you?", "can i know your age", "what is your age"],
  "responses": ["I am 19 years old", "My age is 19", "My birthday is Jan 5th and I was born in 2002, so I am 19 years old !"]
}]
}
```
<br />
## Example Code : 

```php
require_once __DIR__ . '/vendor/autoload.php';

use Chatman\BotWithPatterns;

$bot = new BotWithPatterns("chatbot.json");
$bot->defaultMsg = "I did not understand !";
$bot->train();

$resp = $bot->getResponse("hello");

echo $resp['resp'];
```

