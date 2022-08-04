# tk-mail

__Project:__ tk-mail  
__Project:__ [ttek/tk-map](http://packagist.org/packages/ttek/tk-map)
__Authors:__ Michael Mifsud <http://www.tropotek.com/>  
  
A mail lib for the Tk lib. Uses PHPMailer for the email driver.

## Contents

- [Installation](#installation)
- [Introduction](#introduction)


## Installation

Available on Packagist ([ttek/tk-mail](http://packagist.org/packages/ttek/tk-mail))
and installable via [Composer](http://getcomposer.org/).

```bash
composer require ttek/tk-mail
```

Or add the following to your composer.json file:

```json
"ttek/tk-mail": "~3.2"
```


## Introduction

Basic Example:
```php
$message = new \Tk\Mail\Message();
$message->addTo('info@tropotek.com');
$message->setFrom('godar@tropotek.com.au');
$message->setSubject('This is a test email');
$message->setBody(\App\Config::createMailTemplate('This is some message text'));

$message->send();
```

Available Config Params:

```php
/* Default config options */
$cfg = array();

/*
 * Options mail, smtp, sendmail, qmail
 */
$cfg['mail.driver'] = 'mail';

/*
 * SMTP settings
 */

/**
 * SMTP hosts.
 * Either a single hostname or multiple semicolon-delimited hostnames.
 * You can also specify a different port
 * for each host by using this format: [hostname:port]
 * (e.g. "smtp1.example.com:25;smtp2.example.com").
 * You can also specify encryption type, for example:
 * (e.g. "tls://smtp1.example.com:587;ssl://smtp2.example.com:465").
 * Hosts will be tried in order.
 * @var string
 */
$cfg['mail.smtp.host'] = 'localhost';
// The default SMTP server port.
$cfg['mail.smtp.port'] = 25;
$cfg['mail.smtp.username'] = '';
$cfg['mail.smtp.password'] = '';
// What kind of encryption to use on the SMTP connection. Options: '', 'ssl' or 'tls'
$cfg['mail.smtp.secure'] = '';
// Whether to use SMTP authentication. Uses the Username and Password properties.
$cfg['mail.smtp.enableAuth'] = true;
// Whether to keep SMTP connection open after each message. If this is set to true 
//   then to close the connection requires an explicit call to smtpClose().
$cfg['mail.smtp.enableKeepAlive'] = '';

// Checks if the send command was called from this site
$cfg['mail.checkReferer'] = true;
// Add valid domain names as valid referers if needed
$cfg['mail.validReferers'] = '';

/*
 * Other misc options
 * Generally from the site config
 */
// Set this if you want all email to go to this address during debug
//$cfg['system.debug.email'] = 'debug@'.$_SERVER['HTTP_HOST'];

// \Tk\Request Used to set the X-Sender-IP, X-Referer headers
//$cfg['request'] = '';
// \Tk\Session Used to set the X-SiteReferer header
//$cfg['session'] = '';

// If true then all outgoing messages are sent to the `system.debug.email` address
//$cfg['debug'] = false;

// If set X-Application will be set to this
//$cfg['system.name'] = '';
//$cfg['system.version'] = '';

// Change this to suite your message body encoding
//$cfg['mail.encoding'] = 'UTF-8';


```



