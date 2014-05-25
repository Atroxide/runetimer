# _Runetimer_

A manageable rune ore respawn timer list to help you mine the most ores in Runescape.

## Installation

Runetimer requires a server running PHP and a MySQL database. Place these files in any directory on a web-accessable server.

Execute the following SQL on any MySQL database.

```sql
CREATE TABLE IF NOT EXISTS `timers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` int(4) unsigned DEFAULT NULL,
  `ore` int(11) DEFAULT NULL,
  `time` int(11) DEFAULT NULL,
  `timefinished` int(11) DEFAULT NULL,
  `world` int(11) DEFAULT NULL,
  `population` int(11) NOT NULL DEFAULT '0',
  `language` int(11) NOT NULL DEFAULT '0',
  'version' tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
```

Now open up config.php in a text editor and provide your database's username, password, host, and the name of the database itself like below

```sql
$db = array (
    'host' => 'localhost',
    'user' => 'atroxide',
    'pass' => 'MYPASSWORD',
    'database' => 'runetimer',
);
```

Navigate your web browser to index.php
