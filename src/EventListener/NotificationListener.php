<?php

namespace App\EventListener;

use App\Event\NotificationCreatedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Entity\Notification;

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
        $booking = $notification->getBooking(); // Récupérer la réservation liée

        // 1️⃣ Envoyer la notification à l'organisateur (destinataire principal)
        $this->sendEmailToOrganizer($notification);

        // 2️⃣ Envoyer la notification aux participants (si applicable)
        if ($booking && method_exists($booking, 'getParticipants')) {
            foreach ($booking->getParticipants() as $participant) {
                // Vérifier si le participant doit être notifié et a une adresse email valide
                if ($participant->isNotified() && method_exists($participant, 'getEmail') && $participant->getEmail()) {
                    $this->sendEmailToParticipant($notification, $participant);
                }
            }
        }
    }

    /**
     * 🔹 Envoi de l'email à l'organisateur (destinataire principal)
     */
    private function sendEmailToOrganizer(Notification $notification): void
    {
        $recipient = $notification->getRecipient();

        if (!$recipient || !method_exists($recipient, 'getIdUser') || !$recipient->getIdUser() || !method_exists($recipient->getIdUser(), 'getEmail')) {
            return;
        }

        $email = $recipient->getIdUser()->getEmail();

        $emailMessage = $this->createEmail(
            $email,
            $notification->getTitle(),
            'emails/notification.html.twig', // Template spécifique aux organisateurs
            [
                'notification' => $notification,
                'recipient' => $recipient,
            ]
        );

        $this->mailer->send($emailMessage);
    }

    /**
     * 🔹 Envoi de l'email aux participants
     */
    private function sendEmailToParticipant(Notification $notification, $participant): void
    {
        $email = $participant->getEmail();

        $emailMessage = $this->createEmail(
            $email,
            $notification->getTitle(),
            'emails/notification_participant.html.twig', // Template spécifique aux participants
            [
                'notification' => $notification,
                'participant' => $participant,
            ]
        );

        $this->mailer->send($emailMessage);
    }

    /**
     * 📩 Création d'un email avec attachement de PDF (si disponible)
     */
    private function createEmail(string $to, string $subject, string $template, array $context): TemplatedEmail
    {
        $emailMessage = (new TemplatedEmail())
            ->from(new Address('photostudio13000@gmail.com', 'Bootcamp Admin'))
            ->to(new Address($to))
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        // Vérifier et attacher le PDF si présent
        if (isset($context['notification']) && $context['notification'] instanceof Notification) {
            $this->attachPdfIfExists($context['notification'], $emailMessage);
        }

        return $emailMessage;
    }

    /**
     * 📎 Ajout d'une pièce jointe (PDF) si elle existe
     */
    private function attachPdfIfExists(Notification $notification, TemplatedEmail $emailMessage): void
    {
        if ($notification->getPdfFilename()) {
            $pdfPath = __DIR__ . '/../../public/uploads/notifications/' . $notification->getPdfFilename();

            if (file_exists($pdfPath)) {
                $emailMessage->attachFromPath($pdfPath, $notification->getPdfFilename(), 'application/pdf');
            }
        }
    }
}