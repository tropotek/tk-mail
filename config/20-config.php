<?php
/**
 * Setup phpmailer configuration parameters
 *
 * @author Tropotek <http://www.tropotek.com/>
 */
use Tk\Config;

return function (Config $config)
{

    /*
     * Options (php)mail, smtp, sendmail, qmail
     */
    $config->set('mail.driver', 'mail');

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
     */
    $config->set('mail.smtp.host', 'localhost');
    $config->set('mail.smtp.port', 25);
    $config->set('mail.smtp.username', '');
    $config->set('mail.smtp.password', '');
    // What kind of encryption to use on the SMTP connection. Options: '', 'ssl' or 'tls'
    $config->set('mail.smtp.secure', '');
    // Whether to use SMTP authentication. Uses the Username and Password properties.
    $config->set('mail.smtp.enableAuth', true);
    // Whether to keep SMTP connection open after each message.
    //   If this is set to true then to close the connection requires an explicit call to smtpClose().
    $config->set('mail.smtp.username', false);

    // Checks if the send command was called from this site
    $config->set('mail.checkReferer', true);
    // Add valid domain names as valid referrers if needed
    $config->set('mail.validReferrers', '');

    /*
     * Other misc options
     */

    // Set this if you want all email to go to this address in debug mode
    //$config->set('mail.debug.email', 'user@example.com');

    // If set X-Application will be set to this
    //$config->set('mail.name', 'tk-mail');
    //$config->set('mail.version', ''1.0.0);

    // Change this to suite your message body encoding
    $config->set('mail.encoding', 'UTF-8');

};



