## AlternC: Hosting software suite 

AlternC is a software suite helping system administrators in handling
Web Services management. It should be easy to install, based only on 
OpenSource softwares. 

This software consist of an automatic install and configuration system, a web control panel to manage hosted users and their web services such as domains, email accounts, ftp accounts, web statistics ...

Technically, AlternC is based on Debian GNU/Linux distribution and it depends on other softwares such as Apache, Postfix, 
Mailman (...). It also contains an API documentation so that users can easily customize their web desktop.

This project native tongue is French. However, the packages are available at least in French and English. 

## Documentation

[alternc.com](https://alternc.com)

## Installation


For now, AlternC can be installed as a Debian package. This package 
depends on other softs used by AlternC. Just add those lines to your
/etc/apt/sources.list file : 

```
deb http://debian.alternc.org/ stable main
```

then 

```
apt-get install mysql-server alternc alternc-ssl
```

You may download and install additionnal plugins after installing AlternC :

* alternc-api
* alternc-awstats
* alternc-mailman
* alternc-roundcube (webmail)

Let's go to the [developper page](http://alternc.com) for more information.


## License


AlternC is distributed under the GPL v2 or later license. See `COPYING`.

AlternC's translations (po files) are distributed under the Creative Commons
CC0 license. Don't participate to the translation if you don't agree to publish
your translation using that license.

