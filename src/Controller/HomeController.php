<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods:['GET'])]
    public function index(CategorieRepository $categorieRepository, ProduitRepository $produitRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'categories' => $categorieRepository->findAll(),
            'products' => $produitRepository->findBy([], ['id' => 'DESC']),
            'title' => '  Accueil  ',
            'description' => '  Bienvenue sur notre site de gestion de produits. ',
        ]);
    }

    #[Route('/produit/{id}/show', name: 'product.show', methods:['GET'])]
    public function showProduct(CategorieRepository $categorieRepository, ProduitRepository $produitRepository): Response
    {
        return $this->render('home/show.html.twig', [
            'products' => $produitRepository->findBy([], ['id' => 'DESC']),
            'title' => '  votre produit ',
        ]);
    }
}
