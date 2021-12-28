# PHP Chatman

Chatman is a machine-learning based chatbot library build in PHP. It can generate responses by learning the conversations. Chatman is very easy to use. It can be used for generating auto responses for social media, customer support, ecommerce website, personal fun etc.

First make sure your system has composer installed.

```
composer require yousuf/chatman
```

If you get any error like this :

```
  Your requirements could not be resolved to an installable set of packages.

    Problem 1
    - Root composer.json requires yousuf/chatman ^1.0 -> satisfiable by yousuf/chatman[v1.0.0].
    - yousuf/chatman v1.0.0 requires writecrow/lemmatizer dev-master -> found writecrow/lemmatizer[dev-master] but it does not match your minimum-stability.
```

Then run these commands : 

```
 composer require writecrow/lemmatizer:dev-master
 composer require yousuf/chatman

```
