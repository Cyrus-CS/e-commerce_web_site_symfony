<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategoryFormType;
use App\Repository\CategorieRepository;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

//#[Route('/admin', name: 'admin.')]
final class CategoryController extends AbstractController
{

    #[Route('/category/create', name:'category.create')]
    public function createCategory(Request $req, EntityManagerInterface $em){

        $category = new Categorie();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($req);
        if($form->isSubmitted() && $form->isValid()){
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'La catégorie a été ajoutée avec succès.');
            return $this->redirectToRoute('category.read');
        }
        return $this->render('category/create.html.twig',[
            'title' =>'Categories : ',
            'description'=> ' Ajouter une catégorie ',
            "form" => $form->createView()
        ]);
    }

    #[Route('/category', name: 'category.read')]
    public function readCategory(CategorieRepository $cr, PaginatorInterface $paginator, Request $req): Response{
        $query = $cr->createQueryBuilder('c')->getQuery();

        // Définition du nombre d'éléments par page
        $limit = $req->query->getInt('limit', 5); // Par défaut 10, mais adaptable via le paramètre GET

        // Création de la pagination
        $categories = $paginator->paginate(
            $query,
            $req->query->getInt('page', 1), // Numéro de page
            $limit // Nombre d'éléments par page, adaptatif
        );
        
        return $this->render("category/index.html.twig", [
            'categories' => $categories,
            'title' => '  Categories : ',
            'description' => ' Listes des catégories '
        ]);
    }

    #[Route('/category/update/{id}', name:'category.update')]
    public function updateCategory( Categorie $cat, Request $req, EntityManagerInterface $em, int $id){

        $form = $this->createForm(CategoryFormType::class, $cat);
        $form->handleRequest($req);
        if($form->isSubmitted() && $form->isValid()){
            $em->persist($cat);
            $em->flush();
            $this->addFlash('success', 'La catégorie a été modifiée avec succès.');
            // Redirection vers la liste des catégories
            return $this->redirectToRoute('.category.read');
        }

        return $this->render('category/update.html.twig',[
            'id' => $id,
            'title' =>'Categories : ',
            'description'=> ' Modifier une catégorie ',
            "form" => $form->createView()
        ]);
    }

    #[Route('/category/delete/{id}', name: 'category.delete')]
    public function deleteCategory(Categorie $cat, EntityManagerInterface $em){
        $em->remove($cat);
        $em->flush();
        $this->addFlash('success', 'La catégorie a été supprimée avec succès.');
        // Redirection vers la liste des catégories
        return $this->redirectToRoute('category.read');
    }
}
