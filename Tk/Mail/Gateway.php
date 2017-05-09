<?php
namespace Tk\Mail;

/**
 * Class Gateway
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Gateway
{

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var array
     */
    protected $validReferers = array();

    /**
     * @var \PHPMailer
     */
    protected $mailer = null;

    /**
     * The status of the last sent message
     * @var bool
     */
    protected $lastSent = null;

    /**
     * @var array
     */
    protected $error = array();

    /**
     * The status of the last sent message
     * @var Message
     */
    protected $lastMessage = null;

    /**
     * @var \Tk\Event\Dispatcher
     */
    protected $dispatcher = null;

    /**
     * @var string
     */
    protected $host = 'localhost';


    /**
     * Gateway constructor.
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        $this->params = $params;
        $this->mailer = new \PHPMailer();

        if (isset($this->params['mail.driver'])) {
            // Set the mail driver Default: mail();
            switch ($this->params['mail.driver']) {
                case 'smtp':
                    $this->mailer->isSMTP();
                    $this->mailer->SMTPAuth = $this->params['mail.smtp.enableAuth'];
                    $this->mailer->SMTPKeepAlive = $this->params['mail.smtp.enableKeepAlive'];
                    $this->mailer->SMTPSecure = $this->params['mail.smtp.secure'];
                    $this->mailer->Host = $this->params['mail.smtp.host'];
                    $this->mailer->Port = $this->params['mail.smtp.port'];
                    $this->mailer->Username = $this->params['mail.smtp.username'];
                    $this->mailer->Password = $this->params['mail.smtp.password'];
                    break;
                case 'sendmail':
                    $this->mailer->isSendmail();
                    break;
                case 'qmail':
                    $this->mailer->isQmail();
                    break;
            }
        }

        if (isset($this->params['mail.validReferers'])) {
            $refs = $this->params['mail.validReferers'];
            if (!is_array($refs)) {
                $refs = explode(',',  $refs);
            }
            $this->validReferers = array_merge($this->validReferers, $refs);
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $this->host = $_SERVER['HTTP_HOST'];
            $this->validReferers[] = array_merge($this->validReferers, array($this->host));
        }
    }

    /**
     *
     * @param Message $message
     * @return bool
     * @throws Exception
     */
    public function send(Message $message)
    {
        $this->error = array();
        try {
            if (!count($message->getTo())) {
                throw new Exception('No valid recipients found!');
            }
            if (!$message->getFrom()) {
                throw new Exception('No valid sender email found!');
            }
            $this->checkReferer($this->validReferers);

            if ($message->isHtml()) {
                $this->mailer->msgHTML($message->getParsed());
                $this->mailer->AltBody = strip_tags($message->getParsed());
            } else {
                $this->mailer->Body = $message->getParsed();
            }

            $this->mailer->CharSet = 'UTF-8';
            if (isset($this->params['mail.encoding']) && $this->params['mail.encoding']) {
                $this->mailer->CharSet = $this->params['mail.encoding'];
            }

            foreach ($message->getAttachmentList() as $obj) {
                $this->mailer->addStringAttachment($obj->string, $obj->name, $obj->encoding, $obj->type);
            }

            if (isset($this->params['system.info.project'])) {
                $message->addHeader('X-Application', $this->params['system.info.project']);
                if (isset($this->params['system.info.version'])) {
                    $message->addHeader('X-Application-Version', $this->params['system.info.version']);
                }
            } else {
                $message->addHeader('X-Application', 'tk-lib-app');
                $message->addHeader('X-Application-Version', '0.0.0');
            }

            if (isset($this->params['request']) && $this->params['request'] instanceof \Tk\Request) {
                if ($this->params['request']->getIp())
                $message->addHeader('X-Sender-IP', $this->params['request']->getIp());
                if ($this->params['request']->getReferer())
                    $message->addHeader('X-Referer', $this->params['request']->getReferer());
            }
            if (isset($this->params['session']) && $this->params['session'] instanceof \Tk\Session) {
                $message->addHeader('X-Site-Referer', $this->params['session']->getData('site_referer'));
            }

            if (isset($this->params['debug']) && $this->params['debug']) {  // Send dev emails and headers of live emails if testing or debug
                $testEmail = 'debug@'.$this->host;
                if (isset($this->params['system.debug.email'])) {
                    $testEmail = $this->params['system.debug.email'];
                }
                $this->mailer->Subject = 'Debug: ' . $message->getSubject();
                //to
                $this->mailer->addAddress($testEmail, 'Debug To');
                $message->addHeader('X-Debug-To', Message::listToStr($message->getTo()));
                //From
                $this->mailer->setFrom($testEmail, 'Debug From');
                $message->addHeader('X-Debug-From', $message->getFrom());
                // CC
                if (count($message->getCc())) {
                    $message->addHeader('X-Debug-Cc', Message::listToStr($message->getCc()));
                }
                // BCC
                if (count($message->getBcc())) {
                    $message->addHeader('X-Debug-Bcc', Message::listToStr($message->getBcc()));
                }
            } else {        // Send live emails
                $this->mailer->Subject = $message->getSubject();

                $email = $message->getFrom();
                if (!$email) $email = 'root@' . $this->host;
                list($e, $n) = Message::splitEmail($email);
                $this->mailer->setFrom($e, $n);

                foreach ($message->getTo() as $email) {
                    list($e, $n) = Message::splitEmail($email);
                    $this->mailer->addAddress($e, $n);
                }
                foreach ($message->getCc() as $email) {
                    list($e, $n) = Message::splitEmail($email);
                    $this->mailer->addCC($e, $n);
                }
                foreach ($message->getBcc() as $email) {
                    list($e, $n) = Message::splitEmail($email);
                    $this->mailer->addBCC($e, $n);
                }
            }

            foreach ($message->getHeadersList() as $h => $v) {
                $this->mailer->addCustomHeader($h, $v);
            }

            $event = new MailEvent($this, $message);
            // Dispatch Pre Send Event
            if ($this->dispatcher) {
                $this->dispatcher->dispatch(MailEvents::PRE_SEND, $event);
            }

            // Send Email
            $this->lastMessage = $message;
            $this->lastSent = $this->mailer->send();


            // Dispatch Post Send Event
            if ($this->dispatcher) {
                $this->dispatcher->dispatch(MailEvents::POST_SEND, $event);
            }

        } catch (\Exception $e) {
            $this->lastSent = false;
            $this->error[] = $e->getMessage();
            if ($this->params['debug']) {
                vd($e->__toString());
            }
        }

        $this->mailer->clearAllRecipients();
        $this->mailer->clearAttachments();
        $this->mailer->clearCustomHeaders();
        $this->mailer->clearReplyTos();
        return $this->lastSent;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->error;
    }

    /**
     * Get the last sent message status
     *
     * @return bool
     */
    public function getLastSent()
    {
        return $this->lastSent;
    }

    /**
     * Return the last message that was sent
     *
     * @return Message
     */
    public function getLastMessage()
    {
        return $this->lastMessage;
    }

    /**
     * @return \Tk\Event\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param \Tk\Event\Dispatcher $dispatcher
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return \PHPMailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * See if a string contains any suspicious/injection coding.
     *
     * @param string $str
     * @throws \Tk\Mail\Exception
     */
//    private function validateString($str)
//    {
//        if (!$str) { return; }
//        $badStrings = array("content-type:", "mime-version:", "multipart\/mixed", "content-transfer-encoding:", "bcc:", "cc:", "to:");
//        foreach ($badStrings as $badString) {
//            if (preg_match('/'.$badString.'/i', strtolower($str))) {
//                throw new Exception("'$badString' found. Suspected injection attempt - mail not being sent.");
//            }
//        }
//        if (preg_match("/(%0A|%0D|\\n+|\\r+)/i", $str) != 0) {
//            throw new Exception("newline found in '$str'. Suspected injection attempt - mail not being sent.");
//        }
//    }

    /**
     * check_referer() breaks up the environmental variable
     * HTTP_REFERER by "/" and then checks to see if the second
     * member of the array (from the explode) matches any of the
     * domains listed in the $referers array (declared at top)
     *
     * @param array $referers
     * @throws \Tk\Mail\Exception
     */
    private function checkReferer($referers)
    {
        // do not check referrer for CLI apps
        if (substr(php_sapi_name(), 0, 3) == 'cli') {
            return;
        }
        if (!isset($this->params['mail.checkReferer']) || !$this->params['mail.checkReferer']) {
            return;
        }

        if (count($referers) > 0) {
            if (isset($_SERVER['HTTP_REFERER'])) {
                $temp = explode('/', $_SERVER['HTTP_REFERER']);
                $found = false;
                while (list(, $stored_referer) = each($referers)) {
                    if (preg_match('/^' . $stored_referer . '$/i', $temp[2]))
                        $found = true;
                }
                if (!$found) {
                    throw new Exception("You are coming from an unauthorized domain. Illegal Referer.");
                }
            } else {
                throw new Exception("Sorry, but I cannot figure out who sent you here. Your browser is not sending an HTTP_REFERER. This could be caused by a firewall or browser that removes the HTTP_REFERER from each HTTP request you submit.");
            }
        } else {
            throw new Exception("There are no referers defined. All submissions will be denied.");
        }
    }

}