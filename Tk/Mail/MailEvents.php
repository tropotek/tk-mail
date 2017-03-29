<?php
namespace Tk\Mail;

/**
 * Class MailEvents
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
final class MailEvents
{


    /**
     * Called prior to setting up the mail driver to send a message
     *
     * @event \Tk\Event\Event
     */
    const PRE_SEND = 'mail.onPreSend';



    /**
     * Called after the mail driver has sent the message
     *
     * @event \Tk\Event\Event
     */
    const POST_SEND = 'mail.onPostSend';

}