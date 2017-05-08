<?php
namespace Tk\Mail;

/**
 * This message accepts text templates and replaces {param} with the
 * corosponding value. Use Tk_Mail_TplMessage::get() and Tk_Mail_TplMessage::set()
 * to set the replaceable template variables.
 *
 * The default template param list:
 *  o {subject}
 *  o {siteUrl}
 *  o {requestUri}
 *  o {refererUri}
 *  o {remoteIp}
 *  o {userAgent}
 *  o {ccEmailList}
 *  o {bccEmailList}
 *  o {toEmailList}
 *  o {toEmail}
 *  o {fromEmail}
 *
 *
 */
class CurlyMessage extends Message
{
    
    use \Tk\CollectionTrait;


    /**
     * MessageTemplate constructor.
     * 
     * @param string $body
     * @param string $subject
     * @param string $to
     * @param string $from
     */
    public function __construct($body = '', $subject = '', $to = '', $from = '')
    {
        parent::__construct($body, $subject, $to, $from);
    }

    /**
     * create
     *
     * @param string $body
     * @param string $subject
     * @param string $to
     * @param string $from
     * @return static
     */
    public static function createCurlyMessage($body = '', $subject = '', $to = '', $from = '')
    {
        $obj = self::create($body, $subject, $to, $from);
        
        $request = \Tk\Request::create();
        $config = \Tk\Config::getInstance();

        $obj->set('requestUri', $request->getUri()->toString());
        if ($request->getReferer()) {
            $obj->set('refererUri', $request->getReferer()->toString());
        }
        $obj->set('remoteIp', $request->getIp());
        $obj->set('userAgent', $request->getUserAgent());
        $obj->set('siteUrl', $config->getSiteUrl());
        $obj->set('date', \Tk\Date::create()->format(\Tk\Date::FORMAT_LONG_DATETIME));
        
        return $obj;
    }

    public static function getParamList()
    {
        return array(
            '{subject}',
            '{siteUrl}',
            '{requestUri}',
            '{refererUri}',
            '{remoteIp}',
            '{userAgent}',
            '{ccEmailList}',
            '{bccEmailList}',
            '{toEmailList}',
            '{toEmail}',
            '{fromEmail}'
        );
    }

    /**
     * Returns the a parsed message body ready for sending.
     *
     * @return string
     */
    public function getParsed()
    {
        // generally this is called by the gateway before we sent the message
        // so we should able to parse the message here
        
        $this->set('subject', $this->getSubject());
        $this->set('ccEmailList', implode(', ', $this->getCc()));
        $this->set('bccEmailList', implode(', ', $this->getBcc()));
        $this->set('toEmailList', implode(', ', $this->getTo()));
        $this->set('toEmail', implode(', ', $this->getTo()));
        if ($this->getFrom()) {
            list($fe, $fn) = $this->getFrom();
            $email = $fe;
            if ($fn) {
                $email = $fn . ' <' . $email . '>';
            }
            $this->set('fromEmail', $email);
        }

        $template = \Tk\CurlyTemplate::create($this->getBody());
        return $template->parse($this->getCollection()->all());
    }

}