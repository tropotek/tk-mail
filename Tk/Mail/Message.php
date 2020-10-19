<?php
namespace Tk\Mail;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Message
{
    /**
     * If set to true then emails can contain the users full name
     *  o 	User Name <username@domain.edu.au>
     *
     * If set to false all long email addresses will be cleaned to only contain the email address
     *  o username@domain.edu.au
     *
     * @var bool
     */
    public static $ENABLE_EXTENDED_ADDRESS = true;


    /**
     * @var array
     */
    protected $to = array();

    /**
     * @var array
     */
    protected $cc = array();

    /**
     * @var array
     */
    protected $bcc = array();

    /**
     * @var string
     */
    protected $from = '';

    /**
     * @var string
     */
    protected $subject = '{No Subject}';

    /**
     * @var string
     */
    protected $body = '';

    /**
     * @var bool
     */
    protected $html = true;

    /**
     * @var array
     */
    protected $headerList = array();

    /**
     * @var array
     */
    protected $attachmentList = array();


    /**
     * Message constructor.
     *
     * @param string $body
     * @param string $subject
     * @param string $from
     * @param string $to
     */
    public function __construct($body = '', $subject = '', $to = '', $from = '')
    {
        if ($body) {
            $this->setBody($body);
        }
        if ($subject) {
            $this->setSubject($subject);
        }
        if ($to) {
            $this->addTo($to);
        }
        if ($from) {
            $this->setFrom($from);
        }
    }

    /**
     * @param string $body
     * @param string $subject
     * @param string $to
     * @param string $from
     * 
     * @return static
     */
    public static function create($body = '', $subject = '', $to = '', $from = '')
    {
        $obj = new static($body, $subject, $to, $from);
        return $obj;
    }

    /**
     * The message text body
     *
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Returns the message body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns the a parsed message body ready for sending.
     *
     * @return string
     */
    public function getParsed()
    {
        return $this->getBody();
    }

    /**
     * Set the subject
     *
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Returns the message subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Adds a custom header.
     *
     * @param string $header
     * @param string $value
     * @return Message
     */
    public function addHeader($header, $value = '')
    {
        if (strstr($header, ':') !== false) {
            $this->headerList[] = explode(':', $header, 2);
        } else {
            $this->headerList[$header] = $value;
        }
        return $this;
    }

    /**
     * Get a list of
     *
     * @return array
     */
    public function getHeadersList()
    {
        return $this->headerList;
    }

    /**
     * Replace the message header list
     *
     * @param $array
     * @return $this
     */
    public function setHeaderList($array = array())
    {
        $this->headerList = $array;
        return $this;
    }

    /**
     * Set from email address
     *
     * @param string $email
     * @return $this
     */
    public function setFrom($email)
    {
        $this->from = trim($email);
        return $this;
    }

    /**
     * Return the from address
     *
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Add a recipient address to the message
     *
     * @param string $email
     * @return $this
     */
    public function addTo($email)
    {
        return $this->addAddress($email, $this->to);
    }

    /**
     * Get the to recipient list
     *
     * array(
     *   'email1',
     *   'User Name <email2>'
     * );
     *
     * @return array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @return bool
     */
    public function hasRecipient()
    {
        return (count($this->getTo()) > 0);
    }

    /**
     * Add A Carbon Copy recipient
     *
     * @param string $email
     * @return $this
     */
    public function addCc($email)
    {
        return $this->addAddress($email, $this->cc);
    }

    /**
     * Get the Cc recipient list
     *
     * array(
     *   'email1',
     *   'User Name <email2>'
     * );
     *
     * @return array
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Add a Blind Carbon Copy recipient
     *
     * @param string $email
     * @return $this
     */
    public function addBcc($email)
    {
        return $this->addAddress($email, $this->bcc);
    }

    /**
     * Get the bcc recipient list
     *
     * array(
     *   'email1',
     *   'User Name <email2>'
     * );
     *
     * @return array
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @return array
     */
    public function getRecipients()
    {
        $arr = $this->getTo();
        $arr = array_merge($arr, $this->getCc());
        $arr = array_merge($arr, $this->getBcc());
        return $arr;
    }

    /**
     * Add a recipient address to the message
     * Only for internal usage
     *
     * @param string $email
     * @param array $arr Reference to the array to save the address to
     * @return $this
     */
    private function addAddress($email, &$arr)
    {
        if ($email) {
            $list = self::strToList($email);
            foreach ($list as $e) {
                if (self::isValidEmail($e)) {
                    $arr[] = trim($e);
                }
            }
        }
        return $this;
    }

    /**
     * reset the arrays:
     *  o to
     *  o cc
     *  o bcc
     *
     * @return $this
     */
    public function reset()
    {
        $this->to = array();
        $this->cc = array();
        $this->bcc = array();
        return $this;
    }

    /**
     * Is this message a html message
     * If no parameter given nothing is set but its value returned.
     *
     * @param bool $b (Optional)If not set acts as a getter(accessor) method
     * @return bool
     */
    public function isHtml($b = null)
    {
        if ($b !== null) {
            $this->html = $b;
        }
        return $this->html;
    }

    /**
     * take an email list fom above and return a string
     *
     * @param string|array $list
     * @param string $separator
     * @return string
     */
    public static function listToStr($list, $separator = ',')
    {
        if (is_string($list)) $list = self::strToList($list);
        $str = '';
        foreach ($list as $email) {
            if (!self::isValidEmail($email)) continue;
            $str .= $email . $separator;
        }
        $str = rtrim($str, $separator);
        return $str;
    }

    /**
     * Take a string and break it into a list
     * EG:
     *  'email1@test.org,email2@eample.com,...'
     *  'email1@test.org;email2@eample.com,...'
     *  'email1@test.org:email2@eample.com,...'
     *  'name #1 <email1@test.org>,name #2 <wmail2@test.org>,...'
     *
     * returns a compatible email array for to,cc,bcc, from
     *
     * @param string $str
     * @param string $separator
     * @return array
     * @note There may be a bug here now that we know that email usernames can contain any ascii character
     */
    public static function strToList($str, $separator = ',')
    {
        $str = str_replace(';', ',', $str);
        $str = str_replace(':', ',', $str);
        if ($separator)
            $str = str_replace($separator, ',', $str);
        return explode(',', $str);
    }

    /**
     * split an email address from its parts to an array
     * EG:
     *   o "username@domain.com" = array('username@domain.com', 'username')
     *   o "User Name <username@domain.com>" = array('username@domain.com', 'User Name')
     *   O All unknowns return array('', 'original string value...')
     *
     * @param string $email
     * @return array Containing (email, name)
     */
    public static function splitEmail($email)
    {
        $email = trim($email);
        if (preg_match('/(.+) <(\S+)>/', $email, $regs)) {
            return array(strtolower($regs[2]), $regs[1]);
        } else if (preg_match('/((\S+)@(\S+))/', $email, $regs)) {
            return array(strtolower($email), $regs[2]);
        }
        return array('', $email);
    }

    /**
     *
     * @param string $email
     * @param string $name
     * @return string
     */
    public static function joinEmail($email, $name = '')
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return '';
        if (!$email || !$name || !self::$ENABLE_EXTENDED_ADDRESS) {
            return $email;
        }
        return sprintf('%s <%s>', $name, $email);
    }

    /**
     * @param string $email
     * @return boolean
     */
    public static function isValidEmail($email)
    {
        list($e, $n) = self::splitEmail($email);
        return filter_var($e, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Adds an attachment from a path on the filesystem.
     * Returns false if the file could not be found
     * or accessed.
     *
     * @param string $path Path to the file.
     * @param string $name if null file basename used
     * @param string $type File extension (MIME) type. If null tries to auto detect type.
     * @return $this
     * @throws \Tk\Mail\Exception
     */
    public function addAttachment($path, $name = '', $type = 'application/octet-stream')
    {
        $encoding = 'base64';

        if (!is_readable($path)) {
            throw new Exception('Cannot read file: ' . $path);
        }
        if (!$type) {
            $type = \Tk\File::getMimeType($path);
        }
        if (!$name) {
            $name = basename($path);
        }

        $data = file_get_contents($path);
        return $this->addStringAttachment($data, $name, $encoding, $type);
    }

    /**
     * Get the file attachments
     *
     * @return array|\stdClass[]
     */
    public function getAttachmentList()
    {
        return $this->attachmentList;
    }

    /**
     * @param $array
     * @return $this
     */
    public function setAttachmentList($array = array())
    {
        $this->attachmentList = $array;
        return $this;
    }

    /**
     * Adds a string or binary attachment (non-filesystem) to the list.
     * This method can be used to attach ascii or binary data,
     * such as a BLOB record from a database.
     *
     * @param string $data Binary attachment data.
     * @param string $name Name of the attachment.
     * @param string $encoding File encoding
     * @param string $type File extension (MIME) type.
     * @return $this
     */
    public function addStringAttachment($data, $name, $encoding = 'base64', $type = 'application/octet-stream')
    {
        $obj = new \stdClass();
        $obj->name = $name;
        $obj->encoding = $encoding;
        if ($type == 'application/octet-stream') {      // Try to locate the correct mime if not found
            $mime = \Tk\File::getMimeType($name);
            if ($mime) $type = $mime;
        }
        $obj->type = $type;
        $obj->string = $data;         // This is not encoded, should be raw attachment binary data

        $this->attachmentList[] = $obj;
        return $this;
    }

    /**
     * Return a string representation of this message
     *
     * @return string
     */
    public function toString()
    {
        $str = '';
        $str .= "\nisHtml: " . ($this->isHtml() ? 'Yes' : 'No') . " \n";
        $str .= 'Attachments: ' . count($this->attachmentList) . "\n";

        /* email/name arrays */
        $str .= 'from: ' . $this->getFrom() . "\n";
        $str .= 'to: ' . self::listToStr($this->getTo()) . "\n";
        if (count($this->cc))
            $str .= 'cc: ' . self::listToStr($this->getCc()) . "\n";
        if (count($this->bcc))
            $str .= 'bcc: ' . self::listToStr($this->getBcc()) . "\n";

        $str .= "subject: " . $this->getSubject() . "\n";
        $str .= "body:  \n  " . str_replace($this->getBody(), "\n", "\n  ") . "\n\n";
        return $str;
    }




}