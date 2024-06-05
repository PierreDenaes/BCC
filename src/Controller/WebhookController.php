<?php 

namespace App\Controller;

use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Webhook;

class WebhookController extends AbstractController
{
    #[Route('/webhook', name: 'stripe_webhook')]
    public function stripeWebhook(Request $request, InvoiceRepository $invoiceRepository, EntityManagerInterface $entityManager): Response
    {
        $payload = @file_get_contents('php://input');
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $this->getParameter('stripe_webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return new Response('', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return new Response('', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $invoiceId = $session->client_reference_id;

            $invoice = $invoiceRepository->find($invoiceId);

            if ($invoice) {
                $invoice->setPaidAt(new \DateTime());
                $invoice->getBooking()->setIsPaid(true);

                $entityManager->persist($invoice);
                $entityManager->flush();
            }
        }

        return new JsonResponse(['status' => 'success']);
    }
}
