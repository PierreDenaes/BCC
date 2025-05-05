<?php

namespace App\Controller;

use App\Repository\FaqRepository;
use App\Repository\GalleryRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SiteController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('site/index.html.twig', [
            'products' => $products,
        ]);
    }
    #[Route('/gallery', name: 'gallery_index')]
    public function pageGallery(GalleryRepository $galleryRepository): Response
    {
        $photos = $galleryRepository->findAll();

        return $this->render('site/gallery.html.twig', [
            'photos' => $photos,
        ]);
    }
    #[Route('/cgv', name: 'cgv_index')]
    public function pageCgv(): Response
    {
        return $this->render('site/cgv.html.twig');
    }
    # Mentions  légales
    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function pageMentionsLegales(): Response
    {
        return $this->render('site/mentions_legales.html.twig');
    }
    # Contact
    #[Route('/contact', name: 'contact')]
    public function pageContact(): Response
    {
        return $this->render('site/contact.html.twig');
    }
    # Politique de confidentialité
    #[Route('/politique-de-confidentialite', name: 'politique_de_confidentialite')]
    public function pagePolitiqueDeConfidentialite(): Response
    {
        return $this->render('site/politique_de_confidentialite.html.twig');
    }
    # Politique de cookies
    #[Route('/politique-de-cookies', name: 'politique_de_cookies')]
    public function pagePolitiqueDeCookies(): Response
    {
        return $this->render('site/politique_de_cookies.html.twig');
    }
    # FAQ
    #[Route('/faq', name: 'faq')]
    
    public function pageFaq(FaqRepository $faqRepository): Response
    {
        return $this->render('site/faq.html.twig', [
            'faqs' => $faqRepository->findAll(),
        ]);
    }
}
