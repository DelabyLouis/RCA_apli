<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\Personne;
use App\Form\RegistrationFormType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager, 
        RoleRepository $roleRepository,
        Security $security
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Créer automatiquement une personne correspondante
            $personne = new Personne();
            // Utiliser des valeurs par défaut basées sur le username
            $personne->setNom('Nom');
            $personne->setPrenom($user->getUsername());
            
            // Associer la personne au user (grâce au cascade persist, la personne sera automatiquement persistée)
            $user->setPersonne($personne);

            // Assigner un rôle par défaut (utilisateur)
            $defaultRole = $roleRepository->findOneBy(['libelle' => 'USER']) ?? $roleRepository->findOneBy(['libelle' => 'UTILISATEUR']);
            if ($defaultRole) {
                $user->addRole($defaultRole);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // Connecter automatiquement l'utilisateur après l'inscription
            $security->login($user, 'form_login');

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous êtes maintenant connecté.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}