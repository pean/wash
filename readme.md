# Wash Tiny URL Shortener
Wash is tiny url shortener class that will store a url and return a tiny url from a JSON REST API request. 

It is build to work from the index file and will identify a tiny url and redirect or else continue with your regular website. Some .htaccess magic might be required though.

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

CURL Example:
```
curl —X POST -d '{ "token": "TOKEN","url": "http://wa.se" }' http://mod.local/git/pa/wash/
``

Made with the great [hashids](http://hashids.org).

