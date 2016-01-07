# ErrorDiary

## bootstrap.php
  - define(LOG_DIR, __DIR__ . '/../log');

## RouterFactory.php  
  - $router[] = new Route('error-diary[/<action>]/', 'ErrorDiary:Homepage:default');

## composer.json
```
{
    "require": {
		...
        "petrkysela/error-diary": "^1.0"
	}
    "repositories": [
		{
			"type": "vcs",
			"url":  "git@github.com:kysela-petr/ErrorDiary.git"
		}
	]
}
```