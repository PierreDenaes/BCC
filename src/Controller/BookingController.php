<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Product;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    #[Route('/bookings', name: 'bookings')] 
    public function bookings(BookingRepository $bookingRepository): JsonResponse 
    {
        $bookings = $bookingRepository->findAll(); // Récupère tous les enregistrements de la table Booking
        $events = []; // Tableau pour stocker les événements
    
        foreach ($bookings as $booking) { // Parcours de tous les enregistrements
            $start = clone $booking->getBookAt(); // Clone la date de réservation
            $end = clone $start; // Clone la date de début
    
            // Fixe l'heure de début et de fin en fonction de la durée du produit
            $start->setTime(8, 0); // Début à 8h00
            if ($booking->getProduct()->getDuration() === Product::DURATION_HALF_DAY) { // Si la durée est une demi-journée
                if ($booking->getPeriod() === 'morning') { // Si la période est le matin
                    $end->setTime(12, 0); // Fin à 12h00
                } elseif ($booking->getPeriod() === 'afternoon') { // Si la période est l'après-midi
                    $start->setTime(13, 0);
                    $end->setTime(17, 0); // Fin à 17h00
                }
            } elseif ($booking->getProduct()->getDuration() === Product::DURATION_FULL_DAY) { // Si la durée est une journée complète
                $end->setTime(17, 0); // Fin à 17h00
            } elseif ($booking->getProduct()->getDuration() === Product::DURATION_TWO_DAYS) { // Si la durée est de deux jours
                $end->modify('+1 day')->setTime(17, 0); // Fin à 17h00 le lendemain
            }
    
            $events[] = [ // Ajout de l'événement
                'title' => $booking->getProduct()->getForfait(), // Ajout du forfait
                'start' => $start->format('Y-m-d\TH:i:s'), // Formatage de la date de début
                'end' => $end->format('Y-m-d\TH:i:s'), // Formatage de la date de fin
                'allDay' => false, // Désactive le mode journée entière
                'period' => $booking->getPeriod(),  // Ajout de la période
            ];
        }
    
        return new JsonResponse($events); // Retourne les événements au format JSON
    }
    


    #[Route('/booking/form', name: 'booking_form')]
    public function bookingForm(Request $request): Response
    {
        $date = $request->query->get('date'); // Récupère la date passée en paramètre

        $booking = new Booking(); // Crée une nouvelle réservation
        $dateTime = new \DateTime($date); // Convertit la date en objet DateTime
        $dateTime->setTime(8, 0);  // Fixe l'heure à 8h00
        $booking->setBookAt($dateTime); // Définit la date de réservation

        $form = $this->createForm(BookingType::class, $booking); // Crée le formulaire

        return $this->render('booking/form.html.twig', [
            'form' => $form->createView(), // Envoie le formulaire à la vue
        ]);
    }

    #[Route('/book', name: 'book', methods: ['GET', 'POST'])]
    public function book(Request $request, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        $booking = new Booking(); // Crée une nouvelle réservation
        $form = $this->createForm(BookingType::class, $booking); // Crée le formulaire
        $form->handleRequest($request); // Gère la requête
 
        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
            $product = $booking->getProduct(); // Récupère le produit sélectionné

            // Vérifions la date reçue
            $date = $request->request->get('bookAt'); // Récupère la date
            if (!$date) { // Si la date est manquante
                return new JsonResponse(['error' => 'Date is missing.'], Response::HTTP_BAD_REQUEST); // Retourne une erreur
            }

            $dateTime = new \DateTime($date); // Convertit la date en objet DateTime
            $booking->setBookAt($dateTime); // Définit la date de réservation

            // Set start time to 8:00 AM for all bookings
            $dateTime->setTime(8, 0); // Fixe l'heure à 8h00
            $booking->setBookAt($dateTime); // Définit la date de réservation

          
            if ($product->getDuration() === Product::DURATION_HALF_DAY) { // Si la durée est une demi-journée
                if ($booking->getPeriod() === null) {
                    $booking->setPeriod('morning'); // Définit la période à matin par défaut
                }

                // Vérifie si la période est déjà réservée
                $existingBookings = $bookingRepository->findBy([  // Recherche les réservations existantes
                    'bookAt' => $dateTime,  // Recherche par date
                    'period' => $booking->getPeriod(), // Recherche par période
                ]);

                if (count($existingBookings) > 0) { // Si une réservation existe
                    return new JsonResponse(['error' => 'La période selectionnée est déja réservée'], Response::HTTP_CONFLICT); // Retourne une erreur
                }
            } else {
                $booking->setPeriod(null); // Réinitialise la période
            }

            
            $user = $this->getUser();
            $profile = $user->getProfile(); // Récupère le profil de l'utilisateur
            $booking->setProfile($profile); // Définit le profil de la réservation

            $entityManager->persist($booking); // Persiste la réservation
            $entityManager->flush(); // Enregistre la réservation

            return new JsonResponse(['success' => true], Response::HTTP_OK); // Retourne un succès
        }

        return $this->render('booking/book.html.twig', [ // Retourne la vue
            'form' => $form->createView(),
        ]);
    }


    
}
