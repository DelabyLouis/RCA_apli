<?php

namespace App\Service;

use App\Entity\ConsentementRgpd;
use App\Entity\User;
use App\Repository\ConsentementRgpdRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ConsentementRgpdService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ConsentementRgpdRepository $consentementRepository,
        private RequestStack $requestStack
    ) {}

    /**
     * Enregistre un consentement pour un utilisateur
     */
    public function enregistrerConsentement(
        User $user, 
        string $type, 
        bool $accepte, 
        string $contexte = null
    ): ConsentementRgpd {
        $consentement = new ConsentementRgpd();
        $consentement->setUser($user);
        $consentement->setTypeConsentement($type);
        $consentement->setAccepte($accepte);
        $consentement->setDateConsentement(new \DateTime());
        $consentement->setContexte($contexte);
        
        // Enregistrer l'IP si disponible
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $consentement->setAdresseIp($request->getClientIp());
        }

        $this->entityManager->persist($consentement);
        $this->entityManager->flush();

        return $consentement;
    }

    /**
     * Retire un consentement (le marque comme retiré sans le supprimer)
     */
    public function retirerConsentement(User $user, string $type): bool
    {
        $consentement = $this->consentementRepository->getConsentementValide($user, $type);
        
        if ($consentement) {
            $consentement->setDateRetrait(new \DateTime());
            $this->entityManager->flush();
            return true;
        }
        
        return false;
    }

    /**
     * Vérifie si un utilisateur a un consentement valide pour un type donné
     */
    public function hasValidConsent(User $user, string $type): bool
    {
        return $this->consentementRepository->hasValidConsent($user, $type);
    }

    /**
     * Récupère tous les consentements d'un utilisateur
     */
    public function getConsentementsByUser(User $user): array
    {
        return $this->consentementRepository->getConsentementsByUser($user);
    }

    /**
     * Types de consentements disponibles
     */
    public const CONSENTEMENT_PRIVACY_POLICY = 'privacy_policy';
    public const CONSENTEMENT_COMMUNICATION = 'communication';
    public const CONSENTEMENT_NEWSLETTER = 'newsletter';
    public const CONSENTEMENT_COOKIES_ANALYTICS = 'cookies_analytics';
    public const CONSENTEMENT_COOKIES_MARKETING = 'cookies_marketing';
}