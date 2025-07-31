<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
   #[Route('/admin/users', name: 'app_users')]
    public function index(UserRepository $user): Response
    {   return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'users' => $user->findAll()
        ]);
    }

   #[Route('/admin/users/{id}/to/editor', name: 'app_users_to_editor')]
   public function changerole(EntityManagerInterface $em, User $user): Response
    {   
        $user->setRoles(['ROLE_EDITOR', 'ROLE_USER']);
        $em->flush();
        $this->addFlash('success', 'Le rôle de l\'utilisateur a été modifié avec succès.');
        return $this->redirectToRoute('app_users');
    }
    #[Route('/admin/users/{id}/remove/editor/role', name: 'app_users_remove_editor_role')]
   public function removerole(EntityManagerInterface $em, User $user): Response
    {   
        $user->setRoles([]);
        $em->flush();
        $this->addFlash('success', 'Le rôle de éditeur a bien été retiré avec succès.');
        return $this->redirectToRoute('app_users');
    }
    #[Route('/admin/users/{id}/remove/', name: 'app_users_remove')]
   public function removeUser(EntityManagerInterface $em, $id, UserRepository $repo): Response
    {   
        $user = $repo->find($id);
        $em->remove($user);
        $em->flush();
        $this->addFlash('danger', 'L\'utilisateur a été supprimé avec succès.');
        return $this->redirectToRoute('app_users');
    }
}

