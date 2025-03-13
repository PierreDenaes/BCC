<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Profile;
use App\Form\ProfileType;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
        $bookings = $profile->getBookings();
        $unreadNotifications = 0;

        foreach ($bookings as $booking) {
            $unreadNotifications += $em->getRepository(Notification::class)
                ->count(['booking' => $booking, 'isRead' => false]);
        }

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
        $unreadNotifications = 0;

        foreach ($profile->getBookings() as $booking) {
            $unreadNotifications += $em->getRepository(Notification::class)
                ->count(['booking' => $booking, 'isRead' => false]);
        }
        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete')]
    public function delete(Request $request, EntityManagerInterface $em, UserInterface $user, TokenStorageInterface $tokenStorage, SessionInterface $session): Response
    {
        $profile = $user->getProfile();

        if (!$profile) {
            throw new \Exception("Le profil de l'utilisateur n'existe pas.");
        }

        if (!$this->isCsrfTokenValid('delete' . $profile->getId(), $request->request->get('_token'))) {
            throw new \Exception("Token CSRF invalide.");
        }

        try {
            // Supprimer toutes les notifications associées AVANT de supprimer le profil
            foreach ($profile->getNotifications() as $notification) {
                $em->remove($notification);
            }
            $em->flush();

            $em->remove($profile);
            $em->flush();

            // Déconnecter l'utilisateur
            $tokenStorage->setToken(null);
            $session->invalidate();
        } catch (\Exception $e) {
            dump($e->getMessage());
            die;
        }

        return $this->redirectToRoute('app_home');
    }
    #[Route('/profile/bookings', name: 'app_profile_bookings')]
    public function bookings(EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();

        if (!$user || !$user->getProfile()) {
            throw $this->createAccessDeniedException("Tu dois être connecté pour voir tes réservations.");
        }

        $profile = $user->getProfile();

        // Récupère les réservations associées au profil
        $bookings = $entityManager->getRepository(Booking::class)->findBy([
            'profile' => $profile
        ], ['createdAt' => 'DESC']); // Tri des réservations par date de création

        // 🔔 Ajout du compteur de notifications non lues
        $unreadNotifications = 0;

        foreach ($profile->getBookings() as $booking) {
            $unreadNotifications += $entityManager->getRepository(Notification::class)
                ->count(['booking' => $booking, 'isRead' => false]);
        }

        return $this->render('profile/bookings.html.twig', [
            'profile' => $profile,
            'bookings' => $bookings,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
    #[Route('/profile/booking/{id}', name: 'app_booking_detail', methods: ['GET'])]
    public function show(Booking $booking, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        $profile = $user->getProfile();
        // 🔔 Ajout du compteur de notifications non lues
        $unreadNotifications = 0;

        foreach ($profile->getBookings() as $booking) {
            $unreadNotifications += $entityManager->getRepository(Notification::class)
                ->count(['booking' => $booking, 'isRead' => false]);
        }
        return $this->render('profile/booking_detail.html.twig', [
            'booking' => $booking,
            'unreadNotifications' => $unreadNotifications,
            'profile' => $profile,
        ]);
    }
    #[Route('/profile/notifications', name: 'app_profile_notifications')]
    public function notifications(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        $profile = $user->getProfile();
        if (!$profile) {
            return $this->redirectToRoute('app_profile_new');
        }

        // Récupérer les réservations du profil
        $bookings = $profile->getBookings();
        $notifications = [];

        foreach ($bookings as $booking) {
            $bookingNotifications = $em->getRepository(Notification::class)
                ->findBy(['booking' => $booking], ['createdAt' => 'DESC']);
            $notifications = array_merge($notifications, $bookingNotifications);
        }

        if ($request->isMethod('POST')) {
            $notificationId = $request->request->get('notification_id');

            if ($notificationId) {
                $notification = $em->getRepository(Notification::class)->find($notificationId);

                if ($notification && in_array($notification->getBooking(), $bookings->toArray(), true) && !$notification->isRead()) {
                    $notification->setIsRead(true);
                    $em->flush();
                }
            }

            return $this->redirectToRoute('app_profile_notifications');
        }

        // 🔔 Compter les notifications non lues
        $unreadNotifications = 0;
        foreach ($bookings as $booking) {
            $unreadNotifications += $em->getRepository(Notification::class)
                ->count(['booking' => $booking, 'isRead' => false]);
        }

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
        if (!$notification) {
            throw $this->createNotFoundException("Notification introuvable.");
        }

        $bookings = $profile->getBookings();
        if (!in_array($notification->getBooking(), $bookings->toArray(), true)) {
            throw $this->createNotFoundException("Notification introuvable.");
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $em->flush();
        }

        return $this->redirectToRoute('app_profile_notifications');
    }

    #[Route('/profile/notifications/mark-all-read', name: 'profile_mark_all_read', methods: ['POST'])]
    public function markAllNotificationsRead(EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();

        if (!$user || !$user->getProfile()) {
            throw $this->createAccessDeniedException("Tu dois être connecté pour effectuer cette action.");
        }

        $profile = $user->getProfile();

        // Récupérer les réservations du profil
        $bookings = $profile->getBookings();

        if ($bookings->isEmpty()) {
            $this->addFlash('info', 'Aucune notification à marquer comme lue.');
            return $this->redirectToRoute('app_profile_notifications');
        }

        // Récupérer les notifications liées aux réservations
        $notifications = $entityManager->getRepository(Notification::class)->createQueryBuilder('n')
            ->where('n.booking IN (:bookings)')
            ->andWhere('n.isRead = false')
            ->setParameter('bookings', $bookings)
            ->getQuery()
            ->getResult();

        if (empty($notifications)) {
            $this->addFlash('info', 'Aucune notification non lue.');
            return $this->redirectToRoute('app_profile_notifications');
        }

        // Marquer les notifications comme lues
        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');

        return $this->redirectToRoute('app_profile_notifications');
    }
    #[Route('/profile/notification/delete/{id}', name: 'profile_delete_notification', methods: ['POST'])]
    public function deleteNotification(Notification $notification, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        $profile = $user->getProfile();
        if (!$profile) {
            throw $this->createAccessDeniedException("Vous devez avoir un profil pour supprimer une notification.");
        }

        $bookings = $profile->getBookings();
        if (!in_array($notification->getBooking(), $bookings->toArray(), true)) {
            throw $this->createAccessDeniedException("Tu n'as pas le droit de supprimer cette notification.");
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        $this->addFlash('success', 'Notification supprimée avec succès.');

        return $this->redirectToRoute('app_profile_notifications');
    }
    #[Route('/profile/notifications/ajax', name: 'app_profile_notifications_ajax')]
    public function getNotificationsAjax(EntityManagerInterface $em, UserInterface $user): Response
    {
        $profile = $user->getProfile();

        if (!$profile) {
            return $this->json(['error' => 'Profil introuvable'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer toutes les notifications via les bookings liés au profil
        $bookings = $profile->getBookings();
        $notifications = [];

        foreach ($bookings as $booking) {
            $bookingNotifications = $em->getRepository(Notification::class)
                ->findBy(['booking' => $booking], ['createdAt' => 'DESC']);
            $notifications = array_merge($notifications, $bookingNotifications);
        }

        return $this->render('profile/_notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }
}
