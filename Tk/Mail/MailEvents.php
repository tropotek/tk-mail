<?php
namespace Tk\Mail;

/**
 * Class MailEvents
 *
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
final class MailEvents
{


    /**
     * Called prior to setting up the mail driver to send a message
     *
     * @event \Tk\Mail\MailEvent
     */
    const PRE_SEND = 'mail.onPreSend';



    /**
     * Called after the mail driver has sent the message
     *
     * @event \Tk\Mail\MailEvent
     */
    const POST_SEND = 'mail.onPostSend';

}