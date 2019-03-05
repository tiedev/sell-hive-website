<?php

use Psr\Log\LoggerInterface as Logger;
use Noodlehaus\Config;

class MailService
{
    private $logger;
    private $config;

    private $template;
    private $seller;
    private $mailTo;

    private $mailedSuccessfully;

    public function __construct(Logger $logger, Config $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function mailNewSeller($seller)
    {
        $this->template = 'mail_new_seller';
        $this->seller = $seller;
        $this->mailTo = 'seller';

        $this->mail();
    }

    public function mailPasswordReminder($seller)
    {
        $this->template = 'mail_remind_password';
        $this->seller = $seller;
        $this->mailTo = 'seller';

        $this->mail();
    }

    public function mailLimitRequestOpened($seller)
    {
        $this->template = 'mail_limit_request_opened';
        $this->seller = $seller;
        $this->mailTo = 'admin';

        $this->mail();
    }

    public function getMailedSuccessfully()
    {
        return $this->mailedSuccessfully;
    }

    private function mail()
    {
        $this->logger->debug('mail config', $this->config->get('phpmailer'));

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function ($str, $level) {
            if ($this->config->get('phpmailer.debug')) {
                $this->logger->debug("$level: $str");
            }
        };
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = $this->config->get('phpmailer.encryption');
        $mail->Host = $this->config->get('phpmailer.host');
        $mail->Port = $this->config->get('phpmailer.port');
        $mail->Username = $this->config->get('phpmailer.username');
        $mail->Password = $this->config->get('phpmailer.password');

        $fromMail = $this->config->get('phpmailer.from.mail');
        $fromName = $this->config->get('phpmailer.from.name');
        $mail->setFrom($fromMail, $fromName);
        if ($this->mailTo == 'seller') {
            $mail->addAddress($this->seller->getMail(), $this->seller->getName());
        } else {
            $mail->addAddress($this->config->get('admin.mail'), 'Admin');
        }
        $mail->isHTML(true);
        $mail->Subject = $this->config->get('phpmailer.subject.new');

        $body = $this->loadTemplate();
        $body = str_replace('%name%', $this->seller->getName(), $body);
        $body = str_replace('%id%', $this->seller->getId(), $body);
        $body = str_replace('%mail%', $this->seller->getMail(), $body);
        $body = str_replace('%password%', $this->seller->getPassword(), $body);
        $body = str_replace('%secret%', $this->seller->getPathSecret(), $body);
        $body = str_replace('%baseURL%', $this->config->get('frontend.base'), $body);
        $body = str_replace('%limit%', $this->seller->getLimit(), $body);
        $body = str_replace('%limitRequest%', $this->seller->getLimitRequest(), $body);
        $body = htmlentities($body, ENT_COMPAT | ENT_HTML401, ini_get("default_charset"), false);
        $body = nl2br($body);
        $mail->Body = $body;

        if ($mail->send()) {
            $this->logger->debug('mailed succesfully');
            $this->mailedSuccessfully = true;
        } else {
            $this->logger->error('mailing failed', ['error info' => $mail->ErrorInfo]);
            $this->mailedSuccessfully = false;
        }
    }

    private function loadTemplate()
    {
        $templatePath = 'logic/mail/templates/' . $this->template . '.html';
        $this->logger->debug('load template: ' . $templatePath);
        return file_get_contents($templatePath, true);
    }
}
