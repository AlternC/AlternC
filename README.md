![](https://alternc.com/logo.png)

## AlternC: Web and Email Hosting Software Suite 

AlternC is a software helping system administrators to handle Web and Email services management. It should be easy to install, based only on free software. 

This software consist of an automatic install and configuration system, a web control panel to manage hosted users and their web services such as domains, email accounts, ftp accounts, web statistics...

Technically, AlternC is based on Debian GNU/Linux distribution and it depends on other software such as Apache, Postfix, Dovecot, Mailman (...). It also contains an API documentation so that users can easily customize their web desktop.

This project native language is French, and the code is commented in English. The packages are available at least in French and English, German and Spanish interfaces are usually available too.


## Installation

[To install AlternC, please follow our install documentation](https://alternc.com/Install-en)

[Pour installer AlternC, merci de suivre la documentation d'installation](https://alternc.com/Install-fr)

### Upgrading from AlternC 3.[1,2,3].11 or earlier

The recommended process for upgrading is to upgrade to 3.3.12 before upgrading to 3.5. If
you choose to upgrade directly to 3.5 from an earlier version, please read this information
as there is a risk of a loss of data.

AlternC >= 3.5 removes integrates alternc-ssl and alternc-admintools in the the core AlternC package.

As a result, both alternc-ssl and alternc-admintools will be removed during installation.
When alternc-ssl is removed, it will mark many of the sub domain types suffixed with '-ssl'
and '-mixssl' for deletion.

To safely upgrade between these versions:

* Backup your AlternC database
* Stop the cron service: ```/etc/init.d/cron stop```
* Install alternc (>= 3.5)
* Run the following queries against the alternc database (here, it's called "alternc"): ```
mysql alternc -e 'update sub_domaines set web_action = "OK" where type like "%-mixssl";'
mysql alternc -e 'update sub_domaines set web_action = "OK" where type like "%-ssl";'
```
* Run alternc.install
* Start the cron service again: ```/etc/init.d/cron start```

## Developper information

* This software is built around a Debian package for Stretch whose packaging instructions are located in [debian/](debian/) folder (this package can be installed on Jessie safely too)
* To **build the packages**, clone this repository in a Debian machine and use `debuild` or `dpkg-buildpackage` from source code root.

* The web control panel pages written in PHP are located in [bureau/admin](bureau/admin) and the associated PHP classes doing the stuff are in [bureau/class](bureau/class).

## Nightly build

We have 1 nightly build repositories:
* stretch - [stable 3.5](http://stable-3-5.nightly.alternc.org/)

and 3 nightly from former Debian releases (now unmaintained) 
* jessie - [stable 3.3](http://stable-3-3.nightly.alternc.org/)
* wheezy - [stable 3.2](http://stable-3-2.nightly.alternc.org/)
* squeeze - [stable 3.1](http://stable-3-1.nightly.alternc.org/)

To use one of them, create a file named `/etc/apt/sources.list.d/alternc-nightly-stable-3.5.list` (for debian Jessie or Stretch) as follow :

```
 deb http://stable-3-5.nightly.alternc.org/ latest/
```

The repository and the packages are signed by the pgp key of AlternC nightly build user :

```
wget http://stable-3-5.nightly.alternc.org/nightly.key -O - | apt-key add - 
```

## License

AlternC is distributed under the GPL v2 or later license. See `COPYING`.

AlternC's translations (po files) are distributed under the [Creative Commons CC0 license](https://creativecommons.org/publicdomain/zero/1.0/). Don't participate to the translation if you don't agree to publish your translations under that license.

