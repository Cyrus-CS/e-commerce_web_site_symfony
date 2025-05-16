<?php 

namespace App\EventListener;

use App\Entity\Categorie;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;
use Doctrine\ORM\EntityManagerInterface;

class TwigGlobalListener
{
    // ce listerner va nous permettre d'ajouter des variables globales à twig
    // On va injecter le service twig et le service entity manager
    private Environment $twig;
    private EntityManagerInterface $entityManager;

    // le construncteur de la classe qui va injecter le service twig et le service entity manager
    // Le service entity manager va nous permettre de faire des requêtes sur la base de données
    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    // On va créer une méthode qui va être appelée lors de l'événement kernel.controller
    // Cette méthode va être appelée avant chaque contrôleur
    // On va ajouter une variable globale à twig qui va contenir toutes les catégories
    public function onKernelController(ControllerEvent $event)
    {
        $categories = $this->entityManager->getRepository(Categorie::class)->findAll();
        $this->twig->addGlobal('categories', $categories);
    }
}
