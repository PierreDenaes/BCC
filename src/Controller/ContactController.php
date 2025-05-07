<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from($data['email'])
                ->to('admin@bootcampscenturio.com')
                ->subject('Nouveau message de contact')
                ->html("
                    <div style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>
                        <h2 style='color: #2c3e50;'>📬 Nouveau message de contact</h2>
                        <p><strong>👤 Nom :</strong> {$data['name']}</p>
                        <p><strong>📧 Email :</strong> {$data['email']}</p>
                        <p><strong>💬 Message :</strong></p>
                        <div style='padding: 10px; background-color: #f4f4f4; border-left: 4px solid #2c3e50;'>
                            {$data['message']}
                        </div>
                        <p><strong>📝 Demande de devis :</strong> " . ($data['quoteRequest'] ? 'Oui' : 'Non') . "</p>
                    </div>
                ");

            $mailer->send($email);

            $this->addFlash('success', 'Ton message a bien été envoyé !');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}