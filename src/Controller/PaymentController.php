<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Invoice;
use Stripe\Checkout\Session;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    #[Route('/create-checkout-session/{invoiceId}', name: 'create_checkout_session')]
    public function createCheckoutSession(int $invoiceId, InvoiceRepository $invoiceRepository): Response
    {
        $invoice = $invoiceRepository->find($invoiceId);

        if (!$invoice) {
            return new JsonResponse(['error' => 'Invoice not found.'], Response::HTTP_NOT_FOUND);
        }

        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Booking ' . $invoice->getBooking()->getProduct()->getForfait(),
                    ],
                    'unit_amount' => $invoice->getAmount() * 100, // Stripe expects the amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $session->id]);
    }

    #[Route('/payment-success', name: 'payment_success')]
    public function paymentSuccess(): Response
    {
        return $this->render('payment/success.html.twig');
    }

    #[Route('/payment-cancel', name: 'payment_cancel')]
    public function paymentCancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
