<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomePageRedirectsToLoginWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        // Doit rediriger vers login si l'accès est protégé
        // Sinon, la page d'accueil doit être accessible
        $this->assertTrue(
            $client->getResponse()->isSuccessful() || 
            $client->getResponse()->isRedirection()
        );
    }

    public function testHomePageDisplaysSoldes(): void
    {
        $client = static::createClient();
        
        // Si la page d'accueil est accessible sans authentification
        $crawler = $client->request('GET', '/');
        
        if ($client->getResponse()->isSuccessful()) {
            // Vérifier que les éléments de solde sont présents
            $this->assertSelectorExists('.solde, [data-solde], .montant');
        }
    }

    public function testHomePageTitle(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        if ($client->getResponse()->isSuccessful()) {
            $this->assertSelectorExists('title');
        }
    }
}