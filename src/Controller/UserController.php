<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin.')]
final class UserController extends AbstractController
{
    #[Route('/user', name: 'show.users')]
    public function index(UserRepository $ur): Response
    {
        $users = $ur->findAll();
        return $this->render('users/index.html.twig', [
            'users' => $users,
            'title' => 'Utilisateurs : ',
            'description' => '   Liste de tous les utilisateurs  '
        ]);
    }

    #[Route('/user/{id}/edit', name: 'edit.user')]
    public function changeRole(User $user, EntityManagerInterface $em){
        if (!in_array('ROLE_EDITOR', $user->getRoles())) {
            // Si l'utilisateur n'a pas le rôle ROLE_EDITOR, on lui attribue ce rôle
            $user->setRoles(["ROLE_EDITOR"]);
        } else {
            // Si l'utilisateur a déjà le rôle ROLE_EDITOR, on lui retire ce rôle et on lui attribue ROLE_USER
            $user->setRoles(["ROLE_USER"]);
        }
        $em->flush();
        // Afficher un message de succès
        $this->addFlash('success', 'Rôle modifié avec succès.');
        return $this->redirectToRoute('admin.show.users');
    }

   #[Route('/user/{id}/delete', name: 'delete.user')]
    public function deleteRoleEditor(User $user, EntityManagerInterface $em){
        $roles = $user->getRoles();
        if (in_array('ROLE_EDITOR', $roles)) {
            // Supprimer uniquement le rôle ROLE_EDITOR
            $user->setRoles(array_diff($roles, ['ROLE_EDITOR']));
            $em->flush();
            // Afficher un message de succès
            $this->addFlash('success', 'Rôle ROLE_EDITOR supprimé avec succès.');
        } else {
            $this->addFlash('warning', "L'utilisateur n'a pas le rôle ROLE_EDITOR.");
        }

        return $this->redirectToRoute('admin.show.users');
}


}