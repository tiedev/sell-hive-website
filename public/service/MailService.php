<?php

use Psr\Log\LoggerInterface as Logger;
use Noodlehaus\Config;
use Slim\Views\Twig;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private Logger $logger;
    private Config $config;
    private Twig $twig;

    public function __construct(Logger $logger, Config $config, Twig $twig)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->twig = $twig;
    }

    public function sendWelcomeToSeller(Seller $seller)
    {
        $mail = $this->init();

        $mail->addAddress($seller->getMail(), $seller->getName());
        $mail->Subject = $this->config->get('phpmailer.subject.new');
        $mail->Body = nl2br($this->twig->getEnvironment()->render('mail/SellerWelcome.txt', $this->toContext($seller)));

        return $this->send($mail);
    }

    public function sendPasswordReminderToSeller(Seller $seller)
    {
        $mail = $this->init();

        $mail->addAddress($seller->getMail(), $seller->getName());
        $mail->Subject = $this->config->get('phpmailer.subject.remind');
        $mail->Body = nl2br($this->twig->getEnvironment()->render('mail/SellerRemindPassword.txt', $this->toContext($seller)));

        return $this->send($mail);
    }

    public function sendLimitRequestToAdmin(Seller $seller)
    {
        $mail = $this->init();

        $mail->addAddress($this->config->get('admin.mail'), 'Admin');
        $mail->Subject = $this->config->get('phpmailer.subject.limitRequest');
        $mail->Body = nl2br($this->twig->getEnvironment()->render('mail/LimitRequestOpened.txt', $this->toContext($seller)));

        return $this->send($mail);
    }

    public function sendLimitInfoToSeller(Seller $seller)
    {
        $mail = $this->init();

        $mail->addAddress($seller->getMail(), $seller->getName());
        $mail->Subject = $this->config->get('phpmailer.subject.limitRequest');
        $mail->Body = nl2br($this->twig->getEnvironment()->render('mail/LimitRequestClosed.txt', $this->toContext($seller)));

        return $this->send($mail);
    }

    private function init(): PHPMailer
    {
        $this->logger->debug('mail config', $this->config->get('phpmailer'));

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = $this->config->get('phpmailer.debug') ? 2 : 0;
        $mail->Debugoutput = function ($str, $level) {
            $this->logger->debug("$level: $str");
        };
        $mail->SMTPAuth = $this->config->get('phpmailer.auth');
        $mail->SMTPSecure = $this->config->get('phpmailer.encryption');
        $mail->Host = $this->config->get('phpmailer.host');
        $mail->Port = $this->config->get('phpmailer.port');
        $mail->Username = $this->config->get('phpmailer.username');
        $mail->Password = $this->config->get('phpmailer.password');

        $fromMail = $this->config->get('phpmailer.from.mail');
        $fromName = $this->config->get('phpmailer.from.name');
        $mail->setFrom($fromMail, $fromName);

        $mail->isHTML(true);

        return $mail;
    }

    private function toContext(Seller $seller): array
    {
        $context = array();

        $context['seller']['name'] = $seller->getName();
        $context['seller']['id'] = $seller->getId();
        $context['seller']['mail'] = $seller->getMail();
        $context['seller']['password'] = $seller->getPassword();
        $context['seller']['limit']['current'] = $seller->getLimit();
        $context['seller']['limit']['till'] = $seller->getLimitTill();
        $context['seller']['limit']['requested'] = $seller->getLimitRequest();

        $context['config']['baseUrl'] = $this->config->get('common.baseUrl');

        $context['sender']['mail'] = $this->config->get('phpmailer.from.mail');

        $this->logger->debug('context', $context);

        return $context;
    }

    private function send($mail)
    {
        $successfully = $mail->send();

        if ($successfully) {
            $this->logger->debug('mailed succesfully');
        } else {
            $this->logger->error('mailing failed', ['error info' => $mail->ErrorInfo]);
        }

        return $successfully;
    }
}
