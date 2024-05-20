> [!IMPORTANT]
> Archived May 2024. No longer used or open for changes.


# BCBento

This is the Boston College Libraries' ["bento-box"](http://crl.acrl.org/content/74/3/227.full.pdf) search tool.

## Requirements

* The [Composer](https://getcomposer.org/) dependency management system
* PHP 5.4+

## Installation

1. Clone this repo

        git clone https://github.com/BCLibraries/bcbento.git

2. Install the server dependencies with composer.

        cd bcbento/server
        composer install

3. Change the owner of *server/app/storage* and its subdirectories to the Apache user.

        chmod -R your-apache-user:your-apache-user app/storage

    where `your-apache-user` is the name of your Apache user.

4. Modify your Web server so that */search-services* points to *server/public* and */search* points to *client/app*.

5. Create a *server/.env.php* file based on *server/sample.env.php*
