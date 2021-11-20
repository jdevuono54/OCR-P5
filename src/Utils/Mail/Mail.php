<?php

namespace App\Utils\Mail;

use App\Utils\Twig\TwigManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;

class Mail
{
    /**
     * Permet d'envoyer un mail
     *
     * @param $to
     * @param $subject
     * @param $template
     * @param $context
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public static function send($to, $subject, $template, $context){
        $twigManager = new TwigManager();

        // On parse la config pour récup le smtp
        $conf = parse_ini_file(__DIR__.'\..\..\..\config\config.ini',true);
        $transport = Transport::fromDsn($conf["mailer"]['smtp']);

        $mailer = new Mailer($transport);

        // On crée le mail avec toutes les données
        $email = (new TemplatedEmail())
            ->from(new Address($conf["mailer"]['default_mail']))
            ->to(new Address($to))
            ->subject($subject)
            ->context($context)
            ->htmlTemplate($template);

        $twigBodyRenderer = $twigManager->getBodyRenderer();
        $twigBodyRenderer->render($email);

        // Envoi du mail
        $mailer->send($email);
    }
}