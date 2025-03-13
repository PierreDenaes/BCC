<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use App\Repository\BookingRepository;
use App\Event\NotificationCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class NotificationCrudController extends AbstractCrudController
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getEntityFqcn(): string
    {
        return Notification::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('Détails de la notification'),
            TextField::new('title', 'Titre'),
            TextareaField::new('message', 'Message'),
            // 🔹 Champ Booking lié au profil sélectionné
            AssociationField::new('booking', 'Réservation concernée')
                ->setRequired(false)
                ->setQueryBuilder(function ($qb) {
                    $alias = $qb->getRootAliases()[0]; // Récupérer l'alias principal de l'entité
                    return $qb->orderBy($alias . '.createdAt', 'DESC');
                }),
            BooleanField::new('isRead', 'Lu')
                ->setFormTypeOptions(['attr' => ['disabled' => 'disabled']])
                ->hideOnForm(),
            DateTimeField::new('createdAt', 'Créé le')
                ->hideOnForm(),

            FormField::addPanel('Pièce jointe (PDF)'),
            TextField::new('pdfPath', 'Fichier actuel')
                ->onlyOnIndex(),

            TextField::new('pdfFile')
                ->setFormType(VichFileType::class)
                ->setLabel('Télécharger un fichier PDF')
                ->setFormTypeOptions(['allow_delete' => true])
                ->onlyOnForms(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Notification) {
            return;
        }

        // 🔹 Associer le destinataire à la notification avant de persister
        if ($entityInstance->getBooking()) {
            $entityInstance->setRecipient($entityInstance->getBooking()->getProfile());
        }

        // Persister la notification
        $entityManager->persist($entityInstance);
        $entityManager->flush();

        // Dispatch l'événement pour l'envoi d'un email
        $event = new NotificationCreatedEvent($entityInstance);
        $this->eventDispatcher->dispatch($event, NotificationCreatedEvent::NAME);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Notification) {
            return;
        }

        // 🔹 Associer le destinataire à la notification avant mise à jour
        if ($entityInstance->getBooking()) {
            $entityInstance->setRecipient($entityInstance->getBooking()->getProfile());
        }

        // Sauvegarder la modification
        $entityManager->persist($entityInstance);
        $entityManager->flush();

        // Dispatch de l'événement pour notifier le destinataire
        $entityInstance->setPdfFile(null); // Éviter de passer l'objet File
        $event = new NotificationCreatedEvent($entityInstance);
        $this->eventDispatcher->dispatch($event, NotificationCreatedEvent::NAME);
    }
}
