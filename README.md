# tkr

A bare-bones status feed for self-hosted personal websites.

Currently very much a work in progress, but it's baically functional.

## How it works

Deploy the `/tkr` directory to a web server that supports php. It will work either as the root of a (sub)domain (e.g. tky.mydomain.com) or if served from a subdirectory (e.g. mydomain.com/tkr).

If you serve it from a subdirectory, set the value of `$basePath` in `config/init.php` to the subdirectory name, excluding the trailing slash (e.g. `/tkr`)

It provides an rss feed at `/rss` and an atom feed at `/atom` relative to where it's being served (e.g. `/tkr/rss` if served from `/tkr/`). Each rss entry links to an individual post (which I call "ticks").

## Serving

The document root should be `/PATH/TO/tkr/public`. This will ensure that only the files that need to be accessible from the internet are served by your web server.

## Storage

Ticks are stored in files on the filesystem under `/tkr/ticks`. This directory must be writable by the web server user and so SHOULD NOT be served by the web server. If you set your document root to `/tkr/public/`, then you'll be fine.

The file structure is `YYYY/MM/DD.txt`. That is, each day's ticks are located in a file whose full path is `/tkr/ticks/YEAR/MONTH/DAY.txt`. This is to prevent any single file from getting too large.

Each entry takes the form `TIMESTAMP|TICK`, where `TIMESTAMP` is the time that the entry was made and `TICK` is the text of the entry.

For illustration, here's a sample from the file `/tkr/ticks/2025/05/25` on my test system.

```sh
# cat /tkr/ticks/2025/05/25.txt
23:27:37|some stuff
23:27:45|some more, stuff
```

## Initial config

I'll write this up when I improve it.

## Sample images

Logged out

![tkr homepage - logged out](https://subcultureofone.org/images/tkr-logged-out.png)

Logged in

![tkr homepage - logged in](https://subcultureofone.org/images/tkr-logged-in.png)

RSS

![tkr rss feed](https://subcultureofone.org/images/tkr-rss.png)

Single tick

![tkr single post](https://subcultureofone.org/images/tkr-single.png)

## TODO

* An actual setup script
* Validate CSRF token on tick submission
* Let people set the time zone for ticks
* Support basic custom styling
* Do that config improvement I implied in the previous section
* See if I can get individual ticks to resolve as urls (e.g. /2025/05/26/00/12) rather than doing the query string thing
* Clean up the nginx configs
* Add an .htaccess example
* Maybe h-feed or JSON feed?
* Microformat support?
* A little more profile info?
* Probably a bunch of other stuff I'm not thinking of