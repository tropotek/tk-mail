<?php
namespace Tk\Mail;


/**
 * Class KernelEvent
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 * @notes Adapted from Symfony
 */
class MailEvent extends \Tk\Event\Iface
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
        parent::__construct();
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
     * @return \PHPMailer
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