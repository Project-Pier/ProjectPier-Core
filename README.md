# Project-Pier

**ProjectPier** is a *Free, Open-Source, PHP application* for managing tasks, projects and teams through an intuitive web interface. ( [http://www.projectpier.org](http://www.projectpier.org) )

## Reporting Bugs

It is absolutely critical for you to report any bugs you find with this software.
If you don't, they can not be fixed. If you find a bug please
check the bug tracker to make sure it's not already known.  If you are certain
you have discovered a NEW bug, please log it into the issue tracker here at GitHub.

> If you have **NOT** found any bugs, we need to hear that too!
> Please let us know what type of system you are using and the extent of your
> testing.  Please include the OS, Apache, PHP and MySQL versions and/or the name of
> the web hosting provider the testing was performed on.
> A new forum has been created specifically to gather and discuss this format, it is located at
> [http://www.projectpier.org/forum/development/088]

## System requirements

ProjectPier requires a *PHP web server* and *MySQL*. The recommended web
server is *Apache*, but IIS 5 and above have been reported to work also.

ProjectPier is **not** PHP4 compatible.

### Recommended configuration:

- PHP 5.2 or greater
- MySQL 4.1 or greater with InnoDB support (see notes below)
- Apache 2.0 or greater

If you do not have these installed on a server or your personal computer,
you can visit the sites below to learn more about how to download and install
them.  They are all licensed under various compatible Open Source licenses.

- PHP    : [http://www.php.net/](http://www.php.net/)
- MySQL  : [http://www.mysql.com/](http://www.mysql.com/)
- Apache : [http://www.apache.org/](http://www.apache.org/)

## Upgrading

If you are upgrading an existing ProjectPier installation,
see [upgrade.txt](../master/upgrade.txt) for an upgrade procedure.

## Installation

See [INSTALL.txt](../master/INSTALL.txt)

### Enabling InnoDB Support

Some installations of MySQL don't support *InnoDB* by default.  The ProjectPier installer
will tell you if your server is not configured to support *InnoDB*. This is easy to fix:

1. Open your MySQL options file, the file name is 
   ```my.cnf``` (Linux) - usually at ```/etc/my.cnf```
   or
   ```my.ini``` (Windows) - usually at ```c:/windows/my.ini```
   If you are using the Uniform Server on Windows, the file will be named ```my-small``` 
   and will need to be edited with a unix compatible editor such as *SublimeText, Atom, Vim, ...*
2. Comment the ```skip-innodb``` line by adding ```#``` in front of it (like ```#skip-innodb``` ).
3. It would also be good to increase ```max_allowed_packet``` to ensure that
   you'll be able to upload files larger than 1MB. 
   Just add this line below the ```#skip-innodb``` line:

   ```php
   set-variable = max_allowed_packet=64M
   ```

> Alternatively, just install without *InnoDB* support. The installer will allow you.

### Changing the Language

ProjectPier installation screens are in English and English is the default language
for the program. After installation is complete, the language can be changed.

The following base languages are available:
- nl_nl = Dutch
- en_us = English (US)
- de_de = German
- es_es = Spanish
- fr_fr = French

> Other languages packs may be available for download at the site.

## About ProjectPier

ProjectPier is an Open Source project management and collaboration tool that you can install on your own server. 
It is released under the terms of the Gnu Affero General Public License (AGPL) 
(see [LICENSE](../master/LICENSE) for details).

[http://www.projectpier.org](http://www.projectpier.org)
