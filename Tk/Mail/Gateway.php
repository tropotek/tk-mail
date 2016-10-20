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
     * @var Gateway
     */
    static $instance = null;

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
    protected $mail = null;

    /**
     * The status of the last sent message
     * @var bool
     */
    protected $lastSent = null;

    /**
     * The status of the last sent message
     * @var Message
     */
    protected $lastMessage = null;

    /**
     * @var \Tk\EventDispatcher\EventDispatcher
     */
    protected $dispatcher = null;


    /**
     * Gateway constructor.
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        $this->params = $params;
        $this->mail = new \PHPMailer();

        if (isset($this->params['mail.driver'])) {
            // Set the mail driver Default: mail();
            switch ($this->params['mail.driver']) {
                case 'smtp':
                    $this->mail->isSMTP();
                    $this->mail->SMTPAuth = $this->params['mail.smtp.enableAuth'];
                    $this->mail->SMTPKeepAlive = $this->params['mail.smtp.enableKeepAlive'];
                    $this->mail->SMTPSecure = $this->params['mail.smtp.secure'];
                    $this->mail->Host = $this->params['mail.smtp.host'];
                    $this->mail->Port = $this->params['mail.smtp.port'];
                    $this->mail->Username = $this->params['mail.smtp.username'];
                    $this->mail->Password = $this->params['mail.smtp.password'];
                    break;
                case 'sendmail':
                    $this->mail->isSendmail();
                    break;
                case 'qmail':
                    $this->mail->isQmail();
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
            $this->validReferers[] = array_merge($this->validReferers, array($_SERVER['HTTP_HOST']));
        }
    }

    /**
     *
     * @param array $params
     * @return Gateway
     */
    static function getInstance($params = array())
    {
        if (self::$instance == null) {
            self::$instance = new self($params);
        }
        return self::$instance;
    }

    /**
     *
     * @param Message $message
     * @return bool
     * @throws Exception
     */
    public function send(Message $message)
    {

        if (!count($message->getTo())) {
            throw new Exception('No valid recipients found!');
        }
        if (!count($message->getFrom())) {
            throw new Exception('No valid sender email found!');
        }
        $this->checkReferer($this->validReferers);

        // Dispatch Pre Send Event
        if ($this->dispatcher) {
            $event = new \Tk\EventDispatcher\Event();
            $event->set('gateway', $this);
            $event->set('message', $message);
            $this->dispatcher->dispatch(MailEvents::PRE_SEND, $event);
        }

        if ($message->isHtml()) {
            $this->mail->msgHTML($message->getBody());
            $this->mail->AltBody = strip_tags($message->getBody());
        } else {
            $this->mail->Body = $message->getBody();
        }

        $this->mail->CharSet = 'UTF-8';
        if (isset($this->params['mail.encoding']) && $this->params['mail.encoding']) {
            $this->mail->CharSet = $this->params['mail.encoding'];
        }

        foreach ($message->getAttachmentList() as $obj) {
            $this->mail->addStringAttachment($obj->string, $obj->name, $obj->encoding, $obj->type);
        }

        if (isset($this->params['system.name'])) {
            $h = $this->params['system.name'];
            if (isset($this->params['system.version'])) {
                $h .= ' v:' . $this->params['system.version'];
            }
            $message->addHeader('X-Application', $h);
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
            $testEmail = 'debug@'.$_SERVER['HTTP_HOST'];
            if (isset($this->params['system.debug.email'])) {
                $testEmail = $this->params['system.debug.email'];
            }
            $this->mail->Subject = 'Debug: ' . $message->getSubject();
            //to
            $this->mail->addAddress($testEmail, 'Debug To');
            $message->addHeader('X-Debug-To', Message::listToStr($message->getTo()));
            //From
            $this->mail->setFrom($testEmail, 'Debug From');
            $message->addHeader('X-Debug-From', current($message->getFrom()));
            // CC
            if (count($message->getCc())) {
                $message->addHeader('X-Debug-Cc', Message::listToStr($message->getCc()));
            }
            // BCC
            if (count($message->getBcc())) {
                $message->addHeader('X-Debug-Bcc', Message::listToStr($message->getBcc()));
            }
        } else {        // Send live emails
            $this->mail->Subject = $message->getSubject();

            $f = $message->getFrom();
            if ($f) {
                $this->mail->setFrom($f[0], $f[1]);
            } else {
                $e = 'root@' . $_SERVER['HTTP_HOST'];
                $this->mail->setFrom($e, 'System');
            }

            foreach ($message->getTo() as $e => $n) {
                $this->mail->addAddress($e, $n);
            }
            foreach ($message->getCc() as $e => $n) {
                $this->mail->addCC($e, $n);
            }
            foreach ($message->getBcc() as $e => $n) {
                $this->mail->addBCC($e, $n);
            }
        }

        foreach ($message->getHeadersList() as $h => $v) {
            $this->mail->addCustomHeader($h, $v);
        }

        $this->lastMessage = $message;
        $this->lastSent = $this->mail->send();

        // Dispatch Post Send Event
        if ($this->dispatcher) {
            $event = new \Tk\EventDispatcher\Event();
            $event->set('gateway', $this);
            $event->set('message', $message);
            $this->dispatcher->dispatch(MailEvents::POST_SEND, $event);
        }

        $this->mail->clearAllRecipients();
        $this->mail->clearAttachments();
        $this->mail->clearCustomHeaders();
        $this->mail->clearReplyTos();
        return $this->lastSent;
    }


    /**
     * Gte the last sent message status
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
     * @return \Tk\EventDispatcher\EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param \Tk\EventDispatcher\EventDispatcher $dispatcher
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return \PHPMailer
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * See if a string contains any suspicious/injection coding.
     *
     * @param string $str
     * @throws \Tk\Mail\Exception
     */
    private function validateString($str)
    {
        if (!$str) { return; }
        $badStrings = array("content-type:", "mime-version:", "multipart\/mixed", "content-transfer-encoding:", "bcc:", "cc:", "to:");
        foreach ($badStrings as $badString) {
            if (preg_match('/'.$badString.'/i', strtolower($str))) {
                throw new Exception("'$badString' found. Suspected injection attempt - mail not being sent.");
            }
        }
        if (preg_match("/(%0A|%0D|\\n+|\\r+)/i", $str) != 0) {
            throw new Exception("newline found in '$str'. Suspected injection attempt - mail not being sent.");
        }
    }

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