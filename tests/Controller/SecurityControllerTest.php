<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Connexion');
    }

    public function testLoginForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Vérifier que le formulaire de connexion est présent
        $this->assertSelectorExists('form[action="/login"]');
        $this->assertSelectorExists('input[name="username"]');
        $this->assertSelectorExists('input[name="password"]');
    }

    public function testLogoutRedirect(): void
    {
        $client = static::createClient();
        
        // Tenter d'accéder à logout sans être connecté
        $client->request('GET', '/logout');
        
        // Doit rediriger vers login
        $this->assertResponseRedirects('/login');
    }

    public function testInvalidLogin(): void
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            'username' => 'invalid_user',
            'password' => 'wrong_password',
        ]);
        
        $client->submit($form);
        
        // Doit rediriger vers login avec erreur
        $this->assertResponseRedirects('/login');
        
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger, .error');
    }
}