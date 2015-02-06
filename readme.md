# Wash Tiny URL Shortener
Wash is tiny url shortener composer package that will store a url and return a tiny url from a JSON REST API request. 

It is build to work from the index file and will identify a tiny url and redirect or else continue with your regular website. Some .htaccess magic might be required though.

I use this together with a keyword workfow in [Alfred](http://www.alfredapp.com/) that will copy the shortened url to clipboard.

Hits on shortened url will create a pageview in Google Analytics.

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
https://wa.se/s5f4
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
    'salt' => '[something salty',
    'ga' => array (
      'id' => 'UA-12345-6',
      'site' => 'wa.se'
    )
  )
);
```
