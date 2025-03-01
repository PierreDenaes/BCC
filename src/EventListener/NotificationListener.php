<?php

namespace App\EventListener;

use App\Event\NotificationCreatedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

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

        // Vérifie si le destinataire a un compte utilisateur avec une adresse email
        if (!$recipient || !method_exists($recipient, 'getIdUser') || !$recipient->getIdUser() || !method_exists($recipient->getIdUser(), 'getEmail')) {
            return;
        }
        // Récupère l'adresse email du destinataire
        $email = $recipient->getIdUser()->getEmail();

        // Création de l'email en utilisant le template Twig
        $emailMessage = (new TemplatedEmail())
            ->from(new Address('photostudio13000@gmail.com', 'Bootcamp Admin')) // 📌 Remplace avec ton email
            ->to(new Address($email))
            ->subject($notification->getTitle())
            ->htmlTemplate('emails/notification.html.twig') // Utilisation du template Twig
            ->context([
                'notification' => $notification,
                'recipient' => $recipient,
            ]);

        // Vérifie et attache un fichier PDF si disponible
        // Vérifie et attache un fichier PDF si disponible
        if ($notification->getPdfFilename()) {
            $pdfPath = __DIR__ . '/../../public/uploads/notifications/' . $notification->getPdfFilename();

            if (file_exists($pdfPath)) {
                $emailMessage->attachFromPath($pdfPath, $notification->getPdfFilename(), 'application/pdf');
            }
        }

        // Envoi de l'email
        $this->mailer->send($emailMessage);
    }
}
