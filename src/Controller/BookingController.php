<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Product;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\ProductRepository;
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
        $bookings = $bookingRepository->findAll();
        $events = [];

        foreach ($bookings as $booking) {
            $start = clone $booking->getBookAt();
            $end = clone $start;

            if ($booking->getProduct()->getDuration() === Product::DURATION_HALF_DAY) {
                if ($booking->getPeriod() === 'morning') {
                    $end->setTime(12, 0);
                } elseif ($booking->getPeriod() === 'afternoon') {
                    $start->setTime(13, 0);
                    $end->setTime(17, 0);
                }
            } else {
                $end->modify('+' . $booking->getProduct()->getDuration() . ' hours');
            }

            $events[] = [
                'title' => $booking->getProduct()->getForfait(),
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'allDay' => false
            ];
        }

        return new JsonResponse($events);
    }

    #[Route('/booking/form', name: 'booking_form')]
    public function bookingForm(Request $request): Response
    {
        $date = $request->query->get('date');
        
        $booking = new Booking();
        $booking->setBookAt(new \DateTime($date));

        $form = $this->createForm(BookingType::class, $booking);

        return $this->render('booking/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/book', name: 'book')]
    public function book(Request $request, EntityManagerInterface $entityManager): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $booking->getProduct();

            // Check period if duration is half day
            if ($product->getDuration() === Product::DURATION_HALF_DAY) {
                if ($booking->getPeriod() === null) {
                    $this->addFlash('error', 'Please select a period for half-day bookings.');
                    return $this->redirectToRoute('booking_form', [
                        'date' => $booking->getBookAt()->format('Y-m-d'),
                    ]);
                }
            } else {
                $booking->setPeriod(null); // Clear period for full or multi-day bookings
            }

            // Set the profile from the logged in user
            $user = $this->getUser();
            $profile = $user->getProfile(); // Assurez-vous que la méthode getProfile existe dans votre entité User
            $booking->setProfile($profile);

            $entityManager->persist($booking);
            $entityManager->flush();

            return $this->redirectToRoute('book');
        }

        return $this->render('booking/book.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
