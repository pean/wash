# Wash Tiny URL Shortener
Wash is tiny url shortener composer package that will store a url and return a tiny url from a JSON REST API request. 

It is build to work from the index file and will identify a tiny url and redirect or else continue with your regular website. Some .htaccess magic might be required though.

I use this together with a keyword workfow in [Alfred](http://www.alfredapp.com/) that will copy the shortened url to clipboard.

Wash can also be used with the
[Wash Android App](https://github.com/pean/wash-android)

Hits on shortened url will create a pageview in Google Analytics.

Wash is available on [Packagis](https://packagist.org/packages/pean/wash)


## API Call to create url

POST Payload:
```
{
  "token": "TOKEN",
  "url": "http://wa.se"
}
```

Return: 
```
http://wa.se/s5f4
```

Error response: 
```
{
  "status": 0,
  "errorMsg": "Descriptive error message"
}
```

## CURL Example
```
curl —X POST -d '{ "token": "TOKEN","url": "http://wa.se" }' http://wa.se/
```

# Install

Add this to composer.json:
```
"require": {
  "pean/wash": "dev-master",
},
```

Create the tables from `tables.sql` and then do this:

Set up .htaccess something like this:

```
RewriteEngine on
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^([a-zA-Z0-9]+)$ index.php [L]
```
And the run everything:
```
$wash = new Pean\Wash(
  array(
    'db' => array (
      'host' => 'localhost',
      'user' => 'root',
      'passw' => '',
      'db' => 'wa',
    ),
    'salt' => '[something salty]',
    'ga' => array (
      'id' => 'UA-12345-6',
      'site' => 'wa.se'
    ),
    'test' => '0',
    'pushbullet' = array(
      'token' => '658a17ac3a4ce4b2e80887347a2caf8a'
    )
  )
);
```
