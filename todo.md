#TODO


## Upgrade to LTI v1.3

Use [https://github.com/IMSGlobal/lti-1-3-php-library] 

Add the following to your composer.json file
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/IMSGlobal/lti-1-3-php-library"
    }
],
"require": {
    "imsglobal/lti-1p3-tool": "dev-master"
}
```
Run composer install or composer update In your code, you will now be able to use classes in the \IMSGlobal\LTI namespace to access the library.
