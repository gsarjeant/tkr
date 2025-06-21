# tkr

A lightweight, HTML-only status feed for self-hosted personal websites. Written in PHP. Heavily inspired by [status.cafe](https://status.cafe).

## Screenshots

### Mobile

<img src="https://subcultureofone.org/images/tkr/tkr-logged-out-mobile.png"
     alt="tkr logged out view - mobile"
     width="40%" height="40%">
<img src="https://subcultureofone.org/images/tkr/tkr-logged-in-mobile.png"
     alt="tkr logged in view - mobile"
     width="40%" height="40%">

### Desktop

<img src="https://subcultureofone.org/images/tkr/tkr-logged-out-desktop.png"
     alt="tkr logged in view - desktop"
     width="60%" height="60%">

<img src="https://subcultureofone.org/images/tkr/tkr-logged-in-desktop.png"
     alt="tkr logged in view - desktop"
     width="60%" height="60%">



## Features

* HTML and CSS implementation. No Javascript.
* RSS `/feed/rss` and Atom `/feed/atom` feeds
* CSS uploads for custom theming
* Custom emoji to personalize moods (unicode only)

I'm trying to make sure that the HTML is both semantically valid and accessible, but I have a lot to learn about both. If you see something I should fix, please let me know!

## Prerequisites

* A web server with PHP support, such as:
    * Apache with mod_php
    * nginx and php-fpm
* PHP 8.2+ with the PDO and PDO_SQLITE extensions
    * The PDO and PDO_SQLITE extensions are usually included by default
    * This might work with earlier PHP versions, but I've only tested 8.2

## Installation

1. Download the latest tkr archive from https://subcultureofone.org/files/tkr/tkr.0.6.2.zip
1. Copy the .zip file to your server and extract it
1. Copy the `tkr` directory to the location you want to serve it from
    * on debian-based systems, `/var/www/tkr` is recommended
1. Make the `storage` directory writable by the web server account.
    ```sh
    chown www-data:www-data /path/to/tkr/storage
    chmod 0770 /path/to/tkr/storage
    ```
1. Add the necessary web server configuration.
    * Examples for common scenarios can be found in the [examples](./examples) directory.
        * Apache VPS, subdomain (e.g. `https://tkr.your-domain.com`): [examples/apache/vps/root](./examples/apache/vps/root)
        * Apache VPS, subfolder (e.g. `https://your-domain.com/tkr`): [examples/apache/vps/subfolder](./examples/apache/vps/subfolder)
        * Nginx VPS, subdomain (e.g. `https://tkr.your-domain.com`): [examples/nginx/root](./examples/nginx/root)
        * Nginx VPS, subfolder (e.g. `https://your-domain.com/tkr`): [examples/nginx/subfolder](./examples/nginx/subfolder)
    * Any values that need to be configured for your environment are labeled with `CONFIG`.
    * The SSL configurations are basic, but should work. For more robust SSL configurations, see https://ssl-config.mozilla.org


### From git

If you'd prefer to install from git:

1. Clone this directory and copy the `/tkr` directory to your web server.
    * Required subdirectories are:
        1. `config`
        1. `public`
        1. `src`
        1. `storage`
        1. `templates`
    * Exclude the other directories
2. Follow the main installation from step 4.

## Initial configuration

1. Edit `config/init.php` to set the domain and base path correctly for your configuration.
    * subdirectory installation (e.g. https://my-domain.com/tkr)
    ```
    'base_url' => 'https://my-domain.com',
    'base_path' => '/tkr/',
    ```
    * subdomain installation (e.g. https://tkr.my-domain.com)
    ```
    'base_url' => 'https://tkr.my-domain.com',
    'base_path' => '/',
    ```
1. Browse to your tkr URL. You'll be presented with the setup screen to complete initial configuration.
![tkr setup page](https://subcultureofone.org/images/tkr/tkr-setup.png)

## Server configuration notes

The document root should be `/PATH/TO/tkr/public`. This will ensure that only the files that need to be accessible from the internet are served by your web server.

There is an `.htaccess` file in the `tkr/` root directory. It's designed for the following installation scenario:

* shared hosting
* `tkr/` is installed to `tkr/` under your web root. (e.g. `public_html/tkr`).
* `tkr/public` is the document root
* The other application directories are blocked both by `tkr/.htaccess` and by `.htaccess` files in the directories themselves. These are:
    * `tkr/config`
    * `tkr/examples` (not technically an application directory, but distributed with the .zip archive)
    * `tkr/src`
    * `tkr/storage`
    * `tkr/templates`


### Docker compose

The [docker](./docker) directory contains docker-compose.yml files and web server configs for some different server configurations. For simplicity, these do not use SSL.

To run tkr locally on your machine, copy the docker-compose file you're interested in to `tkr/` and run `docker compose up`.

## Storage

Ticks are stored in files on the filesystem under `/tkr/storage/ticks`. This directory must be writable by the web server user and so SHOULD NOT be served by the web server. If you set your document root to `/tkr/public/`, then you'll be fine.

The file structure is `YYYY/MM/DD.txt`. That is, each day's ticks are located in a file whose full path is `/tkr/storage/ticks/YEAR/MONTH/DAY.txt`. This is to prevent any single file from getting too large.

Each entry takes the form `TIMESTAMP|TICK`, where `TIMESTAMP` is the time that the entry was made and `TICK` is the text of the entry.

For illustration, here's a sample from the file `/tkr/storage/ticks/2025/05/25` on my test system.

```sh
# cat /tkr/ticks/2025/05/25.txt
23:27:37|some stuff
23:27:45|some more, stuff
```

### SQLite Database

tkr stores profile information, custom emojis, and uploaded css metadata in a SQLite database located at `tkr/storage/db/tkr.sqlite`.

You don't have to do any database setup. The database is automatically created and initialized on first run.

## Acknowledgements

It's been a lot of fun to get back to building something. I'm grateful to the people and projects that inspired me to do it:

* [armaina](https://armaina.com) - Armaina's a talented artist (check out the site!) who had the original idea for a self-hosted PHP version of status.cafe. That sounded like a fun project so I thought I'd see if I could manage it. This project doesn't exist without Armaina. Thank you!
* [status.cafe](https://status.cafe) - The technological inspiration. Unless you really want to self-host, you should use status.cafe instead! I took a lot of inspiration from its design and then I made the CSS way heavier and probably lost some of the soul along the way.
* [32-bit cafe](https://32bit.cafe) - I started in technology as a hobbyist and idealist. Then I became a professional. The decades since have sucked the joy and the hope out of technology. 32-bit cafe reminded me that they're both still there.

## TODO

* Add tests
* Add artifact build pipeline
* Validate HTML semantics on all pages
* Validate accessibility on all pages
* Simplify the CSS
* Add logging, including log viewer screen
* Improve exception handling
* Support microformats
* Support h-feed and JSON
* Allow customization of time zone and time display for ticks
* Probably a bunch of other stuff I'm not thinking of