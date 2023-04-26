# Wordpress analytics plugin

WordPress plugin for tracking and storing the tracking data in the 1st party Aesirx Analytics server.

First you will need to set up the 1st party Analytics server.
The instructions are here [AesirX 1st Party Server](https://github.com/aesirxio/analytics-1stparty).

After you set up the server you will install the WordPress plugin and in the configuration you will need to enter
the URL of the 1st party Aesirx Analytics server example [http://example.com:1000/] and publish the plugin.

And this is all set.
The tracking from your WordPress site will be stored in the Mongo database on the 1st party server.

## For local setup

To install this you will need to clone this repo locally with command:

`git clone https://github.com/aesirxio/wordpress-analytics-plugin.git`

## PHP set up

After that you can run the next commands.

`yarn install` - initialize libraries

`yarn build` - for building Joomla zip installer (PHP 7.2 or higher)

`yarn watch` - for watching changes in the JS when developing

## Docker set up

### Linux

Alternatively can be used docker-compose with npm and php included, see available commands in `Makefile`:
_Before build docker container please make sure you set correct USER_ID and GROUP_ID in .env file_

`make init` - initialize libraries

`make build` - for building Joomla zip installer (PHP 7.2 or higher)

`make watch` - for watching changes in the JS when developing

### Windows

If you don't have Makefile set uo on Windows you can use direct docker commands.

`docker-compose run php-npm yarn install` - initialize libraries

`docker-compose run php-npm yarn build` - for building Joomla zip installer (PHP 7.2 or higher)

`docker-compose run php-npm yarn watch` - for watching changes in the JS when developing

## Installing and Set up

After running the build the install package will be created in the `dist` folder.
