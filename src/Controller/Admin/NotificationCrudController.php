<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use App\Event\NotificationCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

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
            AssociationField::new('recipient', 'Destinataire')
                ->setRequired(true),
            BooleanField::new('isRead', 'Lu'),
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

        // Sauvegarder la modification
        $entityManager->persist($entityInstance);
        $entityManager->flush();

        // Dispatch de l'événement pour notifier le destinataire
        $entityInstance->setPdfFile(null); // Éviter de passer l'objet File
        $event = new NotificationCreatedEvent($entityInstance);
        $this->eventDispatcher->dispatch($event, NotificationCreatedEvent::NAME);
    }
}
