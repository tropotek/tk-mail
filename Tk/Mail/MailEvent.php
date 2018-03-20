<?php
namespace Tk\Mail;


/**
 * Class KernelEvent
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 * @notes Adapted from Symfony
 */
class MailEvent extends \Tk\Event\Event
{
    /**
     * @var \Tk\Mail\Gateway
     */
    protected $gateway = null;

    /**
     * @var \Tk\Mail\Message
     */
    protected $message = null;


    /**
     * MailEvent constructor.
     *
     * @param \Tk\Mail\Gateway $gateway
     * @param \Tk\Mail\Message $message
     */
    public function __construct($gateway, $message)
    {
        $this->gateway = $gateway;
        $this->message = $message;
    }

    /**
     * @return Gateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @return \PHPMailer\PHPMailer\PHPMailer
     */
    public function getMailer()
    {
        return $this->getGateway()->getMailer();
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    
}