<?php
namespace Tk\Mail;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Tropotek <info@tropotek.com>
 */
class MailEvent extends Event
{

    protected Gateway $gateway;

    protected Message $message;


    public function __construct(Gateway $gateway, Message $message)
    {
        $this->gateway = $gateway;
        $this->message = $message;
    }

    public function getGateway(): Gateway
    {
        return $this->gateway;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

}