<?php

namespace App\Controller;

use App\Entity\History;
use App\Entity\Produit;
use App\Form\HistoryTypeForm;
use App\Form\ProductUpdateTypeForm;
use App\Form\ProduitForm;
use App\Repository\HistoryRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    #[Route(name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/nouveau', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitForm::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('images')->getData();

            if($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = $slugger->slug($originalFilename);
                // this is needed to safely include the file name as part of the URL
                // On remplace les espaces par des tirets
                $newFilename = $safeName . ' - ' .uniqid().'.'.$image->guessExtension(); // on recupère l'extension de l'image

                try {
                    $image->move(
                        $this->getParameter('image_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                    
                }
                // Stocker le nom de l'image dans la base de données
                $produit->setImages($newFilename);
                
            }
            $entityManager->persist($produit);
            $entityManager->flush();

            //On crée un nouvel historique 
            $stockHistory = new History();
            //On insert les données qui ont été saisi pour remplir un produit directement dans l'historique
            $stockHistory->setQte($produit->getStock())
                                ->setProduit($produit)
                                ->setCreatedAt(new \DateTimeImmutable());
            // On ajoute le produit dans la base de donnée dans Historique 
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductUpdateTypeForm::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/produit/{id}', name: 'add_product_stock', methods: ['POST','GET'])]
    public function addStock(int $id, EntityManagerInterface $entityManagerInterface, Request $request, ProduitRepository $produitRepository)
    {
        $historyStock  = new History();
        $form = $this->createForm(HistoryTypeForm::class, $historyStock);
        $form->handleRequest($request);
        //On récupère les produits
        $produit = $produitRepository->find($id);

        if($form->isSubmitted() && $form->isValid())
        {
            if($historyStock->getQte() > 0)
            {
                //On fait la mise à jour du produit 
                //si on soumet le formulaire, on fait d'abord la mise à jour 
                $newQte = $produit->getStock() + $historyStock->getQte();
                $produit->setStock($newQte);
                
                $historyStock->setCreatedAt(new \DateTimeImmutable());
                $historyStock->setProduit($produit);
                
                $entityManagerInterface->persist($historyStock);
                $entityManagerInterface->flush();

                $this->addFlash('success', 'le stock de votre produit a bien été modifier ');
                return $this->redirectToRoute('app_produit_index');
            }else{

                $this->addFlash('danger', 'Le stock ne doit pas être inferieur à 0 ');
                return $this->redirectToRoute('add_product_stock', [
                    'id' => $produit->getId()
                ]);

            }

        }
        return $this->render('produit/stock.html.twig', [
            'form' => $form->createView(),
            'product' => $produit,
            'title' => 'Ajouter le stock']);
    }

    #[Route('/add/{id}/historique', name: 'app_product_stock_add_history', methods:['GET', 'POST'])]
    public function productAddHistory(int $id, ProduitRepository $produitRepository, HistoryRepository $historyRepository){
        //On récupère le produit passé en parmètre 
        $product = $produitRepository->find($id);
        $productHistory = $historyRepository->findBy(['produit' => $product], ['id' => 'DESC']);

        return $this->render('produit/addProduit.html.twig', [
            'title' => 'Historique des produits',
            'productsAdded' => $productHistory
        ]);
    }
}
