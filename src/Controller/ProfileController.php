<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\ProfileType;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        $profile = $user->getProfile();

        if (!$profile) {
            $profile = new Profile();
            $profile->setIdUser($user);

            $form = $this->createForm(ProfileType::class, $profile);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($profile);
                $em->flush();

                return $this->redirectToRoute('app_profile'); // Redirige vers la page de profil après l'enregistrement
            }

            return $this->render('profile/new.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        // 🔔 Ajout du compteur de notifications non lues
        $unreadNotifications = $em->getRepository(Notification::class)
            ->count(['recipient' => $profile, 'isRead' => false]);

        return $this->render('profile/index.html.twig', [
            'profile' => $profile,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        $profile = $user->getProfile();

        if (!$profile) {
            return $this->redirectToRoute('app_profile_new');
        }

        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_profile');
        }
        // 🔔 Ajout du compteur de notifications non lues
        $unreadNotifications = $em->getRepository(Notification::class)
            ->count(['recipient' => $profile, 'isRead' => false]);
        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete')]
    public function delete(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        $profile = $user->getProfile();

        if ($profile && $this->isCsrfTokenValid('delete' . $profile->getId(), $request->request->get('_token'))) {
            $em->remove($profile);
            $em->flush();
        }

        return $this->redirectToRoute('app_home');
    }
    #[Route('/profile/notifications', name: 'app_profile_notifications')]
    public function notifications(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        $profile = $user->getProfile();

        if (!$profile) {
            return $this->redirectToRoute('app_profile_new');
        }

        $notifications = $em->getRepository(Notification::class)
            ->findBy(['recipient' => $profile], ['createdAt' => 'DESC']);

        if ($request->isMethod('POST')) {
            $notificationId = $request->request->get('notification_id');

            if ($notificationId) {
                $notification = $em->getRepository(Notification::class)->find($notificationId);

                if ($notification && $notification->getRecipient() === $profile && !$notification->isRead()) {
                    $notification->setIsRead(true);
                    $em->flush();
                }
            }

            return $this->redirectToRoute('app_profile_notifications');
        }
        // 🔔 Ajout du compteur de notifications non lues
        $unreadNotifications = $em->getRepository(Notification::class)
            ->count(['recipient' => $profile, 'isRead' => false]);
        return $this->render('profile/notifications.html.twig', [
            'profile' => $profile,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
    #[Route('/profile/notification/read/{id}', name: 'app_profile_notification_read')]
    public function markNotificationAsRead(int $id, EntityManagerInterface $em, UserInterface $user): Response
    {
        $profile = $user->getProfile();

        if (!$profile) {
            throw $this->createAccessDeniedException("Vous devez avoir un profil pour voir vos notifications.");
        }

        $notification = $em->getRepository(Notification::class)->find($id);

        if (!$notification || $notification->getRecipient() !== $profile) {
            throw $this->createNotFoundException("Notification introuvable.");
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $em->flush();
        }

        return $this->redirectToRoute('app_profile_notifications');
    }
    #[Route('/profile/notifications/ajax', name: 'app_profile_notifications_ajax')]
    public function getNotificationsAjax(EntityManagerInterface $em, UserInterface $user): Response
    {
        $profile = $user->getProfile();

        if (!$profile) {
            return $this->json(['error' => 'Profil introuvable'], Response::HTTP_FORBIDDEN);
        }

        $notifications = $em->getRepository(Notification::class)
            ->findBy(['recipient' => $profile], ['createdAt' => 'DESC']);

        return $this->render('profile/_notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }
    #[Route('/profile/notification/delete/{id}', name: 'profile_delete_notification', methods: ['POST'])]
    public function deleteNotification(Notification $notification, EntityManagerInterface $entityManager, Security $security): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire de la notification
        $user = $security->getUser();
        if ($notification->getRecipient()->getIdUser() !== $user) {
            throw $this->createAccessDeniedException("Tu n'as pas le droit de supprimer cette notification.");
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        $this->addFlash('success', 'Notification supprimée avec succès.');

        return $this->redirectToRoute('app_profile_notifications');
    }
}
