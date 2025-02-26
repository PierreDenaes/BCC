<?php

namespace App\Command;

use App\Entity\Notification;
use App\Event\NotificationCreatedEvent;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:test-notification',
    description: 'Crée une notification de test pour un profil spécifique et déclenche son envoi.',
)]
class TestNotificationCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ProfileRepository $profileRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->profileRepository = $profileRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Crée une notification de test pour un profil spécifique.')
            ->addArgument('profileId', InputArgument::REQUIRED, 'ID du profil à notifier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profileId = $input->getArgument('profileId');

        if (!is_numeric($profileId)) {
            $output->writeln('<error>L\'ID du profil doit être un nombre valide.</error>');
            return Command::FAILURE;
        }

        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            $output->writeln('<error>Profil avec l\'ID '.$profileId.' introuvable.</error>');
            return Command::FAILURE;
        }

        $notification = new Notification();
        $notification->setTitle("Nouvelle info sur votre bootcamp !");
        $notification->setMessage("Un changement important a été effectué. Consultez votre profil.");
        $notification->setRecipient($profile);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        // Dispatcher l'événement
        $event = new NotificationCreatedEvent($notification);
        $this->eventDispatcher->dispatch($event, NotificationCreatedEvent::NAME);

        $output->writeln('<info>Notification créée et événement dispatché pour le profil ID '.$profileId.'.</info>');

        return Command::SUCCESS;
    }
}