<?php

namespace App\EventListener;

use App\Event\NotificationCreatedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class NotificationListener
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function onNotificationCreated(NotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification();
        $recipient = $notification->getRecipient();

        // Vérifie si le destinataire est valide et possède un email
        if (!$recipient || !method_exists($recipient, 'getIdUser') || !method_exists($recipient->getIdUser(), 'getEmail')) {
            return;
        }

        $email = $recipient->getIdUser()->getEmail();

        // Création de l'email avec le message et le titre
        $emailMessage = (new Email())
            ->from(new Address('photostudio13000@gmail.com', 'Bootcamp Admin')) // 📌 Remplace avec ton email
            ->to(new Address($email))
            ->subject($notification->getTitle())
            ->text($notification->getMessage())
            ->html('<p>' . nl2br($notification->getMessage()) . '</p>'); // Convertit les sauts de ligne

        // Vérifie si une pièce jointe existe et l'ajoute
        $attachmentPath = $notification->getPdfPath();
        if ($attachmentPath && file_exists($attachmentPath)) {
            $emailMessage->attachFromPath($attachmentPath, 'document.pdf', 'application/pdf');
        }

        // Envoi de l'email
        $this->mailer->send($emailMessage);
    }
}