<?php
namespace Tk\Mail;

use Bs\Config;
use \PHPMailer\PHPMailer\PHPMailer;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
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
     * @var PHPMailer
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
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
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
        // NOTICE: this could be the \Tk\Config object...
        $this->params = $params;
        $this->mailer = new PHPMailer();

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
     * @throws \Exception
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

            $event = new MailEvent($this, $message);
            // Dispatch Pre Send Event
            if ($this->dispatcher) {
                $this->dispatcher->dispatch(MailEvents::PRE_SEND, $event);
            }

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


            $message->addHeader('X-Application', 'www.tropotek.com');
            $message->addHeader('X-Application-Name', 'www.tropotek.com');
            $message->addHeader('X-Application-Version', '0.0.1');

            if (isset($this->params['system.info.project'])) {
                $message->addHeader('X-Application', $this->params['system.info.project']);
            }
            if (!empty($this->params['site.title'])) {
                $message->addHeader('X-Application-Name', $this->params['site.title']);
            }
            if (isset($this->params['system.info.version'])) {
                $message->addHeader('X-Application-Version', $this->params['system.info.version']);
            }

            /** @var \Tk\Request $request */
            $request = null;
            if (!empty($this->params['request']) && $this->params['request'] instanceof \Tk\Request)
                $request = $this->params['request'];

            if ($request) {
                if ($request->getClientIp())
                    $message->addHeader('X-Sender-IP', $request->getClientIp());
                if ($request->getTkUri()->getHost())
                    $message->addHeader('X-Host', $request->getTkUri()->getHost());
                if ($request->getReferer())
                    $message->addHeader('X-Referer', $request->getReferer()->getRelativePath());
            }

            $this->mailer->Subject = $message->getSubject();

            if (isset($this->params['debug']) && $this->params['debug']) {  // Send dev emails and headers of live emails if testing or debug

                $message->addHeader('X-Debug-To', Message::listToStr($message->getTo()));
                $message->addHeader('X-Debug-From', $message->getFrom());

                // Set new debug recipient
                $testEmail = 'debug@'.$this->host;
                if (isset($this->params['system.debug.email'])) {
                    $testEmail = $this->params['system.debug.email'];
                    if (is_array($this->params['system.debug.email'])) {
                        foreach ($this->params['system.debug.email'] as $i => $em) {
                            if ($i == 0) {
                                $testEmail = $em;
                                $this->mailer->addAddress($em, 'Debug To');
                            } else {
                                $this->mailer->addCC($em, 'Debug To');
                            }
                        }
                    } else {
                        $testEmail = $this->params['system.debug.email'];
                        $this->mailer->addAddress($testEmail, 'Debug To');
                    }
                }

                if ($this->params['system.debug.email.authUser'] && class_exists('\Bs\Config') && Config::getInstance()->getAuthUser()) {
                    $testEmail = Config::getInstance()->getAuthUser()->getEmail();
                    $this->mailer->addAddress($testEmail, Config::getInstance()->getAuthUser()->getName());
                }

                $this->mailer->setFrom($testEmail, 'Debug From');

                if (count($message->getCc())) {
                    $message->addHeader('X-Debug-Cc', Message::listToStr($message->getCc()));
                }
                if (count($message->getBcc())) {
                    $message->addHeader('X-Debug-Bcc', Message::listToStr($message->getBcc()));
                }
            } else {        // Send live emails
                $email = $message->getFrom();
                if (!$email) $email = 'noreply@' . $this->host;
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

            // Send Email
            $this->lastMessage = $message;

            // Note: can interfear with output buffer contents in AJAX calls, so enable when needed only
//            if (\Tk\Config::getInstance()->isDebug())
//                $this->mailer->SMTPDebug = 2;

            $this->lastSent = $this->mailer->send();
            if (!$this->lastSent)
                throw new \Tk\Mail\Exception($this->mailer->ErrorInfo);

            // Dispatch Post Send Event
            if ($this->dispatcher) {
                $this->dispatcher->dispatch(MailEvents::POST_SEND, $event);
            }

        } catch (\Exception $e) {
            // TODO: Discuss if this is the best way or should we catch exceptions externally, that may be a better option...???????s
            $this->lastSent = false;
            $this->error[] = $e->getMessage();
            throw $e;
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
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return PHPMailer
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
                foreach ($referers as $k => $stored_referer) {
                    if (preg_match('/^' . $stored_referer . '$/i', $temp[2])) {
                        $found = true;
                        break;
                    }
                }
//                while (list(, $stored_referer) = each($referers)) {
//                    if (preg_match('/^' . $stored_referer . '$/i', $temp[2]))
//                        $found = true;
//                }
                if (!$found) {
                    throw new Exception("You are coming from an unauthorized domain. Illegal Referer.");
                }
            } else {
                throw new Exception("Sorry, but I cannot figure out who sent you here. Your browser is not sending an HTTP_REFERER. This could be caused by a firewall or browser that removes the HTTP_REFERER from each HTTP request you submit.");
            }
        } else {
            throw new Exception("There is no referer defined. All submissions will be denied.");        }
    }

}