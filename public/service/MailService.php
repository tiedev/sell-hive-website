<?php

use Propel\Runtime\Exception\PropelException;
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

    public function sendWelcomeToSeller(Seller $seller): bool
    {
        try {
            $mail = $this->init();

            $mail->addAddress($seller->getMail(), $seller->getName());
            $mail->Subject = $this->config->get('phpmailer.subject.new');
            $mail->Body = $this->makeHtmlReady($this->twig->getEnvironment()->render('mail/SellerWelcome.txt', $this->toContext($seller)));

            return $this->send($mail);
        } catch (Exception $ex) {
            $this->logger->critical('exception while sending welcome to seller', array($ex));
            return false;
        }
    }

    public function sendPasswordReminderToSeller(Seller $seller): bool
    {
        try {
            $mail = $this->init();

            $mail->addAddress($seller->getMail(), $seller->getName());
            $mail->Subject = $this->config->get('phpmailer.subject.remind');
            $mail->Body = $this->makeHtmlReady($this->twig->getEnvironment()->render('mail/SellerRemindPassword.txt', $this->toContext($seller)));

            return $this->send($mail);
        } catch (Exception $ex) {
            $this->logger->critical('exception while sending password reminder to seller', array($ex));
            return false;
        }
    }

    public function sendLimitRequestToAdmin(Seller $seller): bool
    {
        try {
            $mail = $this->init();

            $mail->addAddress($this->config->get('admin.mail'), 'Admin');
            $mail->addReplyTo($seller->getMail(), $seller->getName());
            $mail->Subject = $this->config->get('phpmailer.subject.limitRequest');
            $mail->Body = $this->makeHtmlReady($this->twig->getEnvironment()->render('mail/LimitRequestOpened.txt', $this->toContext($seller)));

            return $this->send($mail);
        } catch (Exception $ex) {
            $this->logger->critical('exception while sending limit request to admin', array($ex));
            return false;
        }
    }

    public function sendLimitInfoToSeller(Seller $seller): bool
    {
        try {
            $mail = $this->init();

            $mail->addAddress($seller->getMail(), $seller->getName());
            $mail->Subject = $this->config->get('phpmailer.subject.limitRequest');
            $mail->Body = $this->makeHtmlReady($this->twig->getEnvironment()->render('mail/LimitRequestClosed.txt', $this->toContext($seller)));

            return $this->send($mail);
        } catch (Exception $ex) {
            $this->logger->critical('exception while sending limit info to seller', array($ex));
            return false;
        }
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
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

        $mail->isHTML();

        return $mail;
    }

    /**
     * @throws PropelException
     */
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
            $this->logger->debug('mailed successfully');
        } else {
            $this->logger->error('mailing failed', ['error info' => $mail->ErrorInfo]);
        }

        return $successfully;
    }

    private function makeHtmlReady($input): string
    {
        return nl2br(str_replace(
            array("Ä", "Ö", "Ü", "ä", "ö", "ü", "ß"),
            array("&Auml;", "&Ouml;", "&Uuml;", "&auml;", "&ouml;", "&uuml;", "&szlig;"),
            $input
        ));
    }
}
