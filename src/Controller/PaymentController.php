<?php

// src/Controller/PaymentController.php
namespace App\Controller;

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PaymentController extends AbstractController
{
    private $logger;
    private $mailer;

    public function __construct(LoggerInterface $logger, MailerInterface $mailer)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    #[Route('/create-checkout-session/{invoiceId}', name: 'create_checkout_session')]
    public function createCheckoutSession(int $invoiceId, InvoiceRepository $invoiceRepository, EntityManagerInterface $em): Response
    {
        $invoice = $invoiceRepository->find($invoiceId);

        if (!$invoice) {
            return new JsonResponse(['error' => 'Invoice not found.'], Response::HTTP_NOT_FOUND);
        }
        $productImageUrl = 'https://127.0.0.1:8000/images/bootcamps/' . $invoice->getBooking()->getProduct()->getBgName();
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Booking ' . $invoice->getBooking()->getProduct()->getForfait(),
                        'images' => [$productImageUrl],
                    ],
                    'unit_amount' => $invoice->getAmount() * 100, // Stripe expects the amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $invoice->setStripeSessionId($session->id);
        $em->persist($invoice);
        $em->flush();

        $this->logger->info('Checkout session created', ['sessionId' => $session->id, 'invoiceId' => $invoiceId]);

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

    #[Route('/webhook', name: 'stripe_webhook')]
    public function stripeWebhook(Request $request, InvoiceRepository $invoiceRepository, EntityManagerInterface $em): Response
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $request->headers->get('stripe-signature');
        $endpoint_secret = $this->getParameter('stripe_webhook_secret');

        $this->logger->info('Webhook received', ['payload' => $payload, 'signature' => $sig_header]);

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            $this->logger->info('Webhook event constructed', ['event' => $event]);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Invalid payload', ['exception' => $e]);
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Invalid signature', ['exception' => $e]);
            return new Response('Invalid signature', 400);
        }

        // Handle the checkout.session.completed event
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $this->logger->info('Checkout session completed', ['sessionId' => $session->id]);

            // Retrieve the invoice from your database
            $invoice = $invoiceRepository->findOneByStripeSessionId($session->id);

            if ($invoice) {
                $booking = $invoice->getBooking();
                $invoice->setPaidAt(new \DateTime());
                $booking->setIsPaid(true);

                $em->persist($invoice);
                $em->persist($booking);
                $em->flush();

                // Send email notifications
                $this->sendEmail(
                    $this->mailer,
                    'contact@bootcampscenturio.com',
                    $booking->getProfile()->getIdUser()->getEmail(),
                    'Confirmation de réservation',
                    '<p>Votre réservation a été confirmée.</p>'
                );

                $this->sendEmail(
                    $this->mailer,
                    'contact@bootcampscenturio.com',
                    'pierre.contact@dnadaweb.fr',
                    'Nouvelle réservation payée',
                    '<p>Une nouvelle réservation a été payée.</p>'
                );

                $this->logger->info('Invoice and booking updated, email notifications sent', ['invoiceId' => $invoice->getId(), 'bookingId' => $booking->getId()]);
            } else {
                $this->logger->error('Invoice not found', ['stripeSessionId' => $session->id]);
                return new Response('Invoice not found', 404);
            }
        }

        return new Response('Success', 200);
    }

    private function sendEmail(MailerInterface $mailer, string $from, string $to, string $subject, string $htmlContent): void
    {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        $mailer->send($email);
    }
}
