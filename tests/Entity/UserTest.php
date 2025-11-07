<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Role;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('hashedpassword123');
        
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('hashedpassword123', $user->getPassword());
    }

    public function testUserRoles(): void
    {
        $user = new User();
        $role = new Role();
        $role->setLibelle('ADMIN');
        
        $user->addRole($role);
        
        $this->assertCount(1, $user->getUserRoles());
        $this->assertTrue($user->hasRole($role));
        
        // Test que le rôle est dans le tableau des rôles Symfony
        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles); // Rôle par défaut
    }

    public function testUserIdentifier(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        
        $this->assertEquals('testuser', $user->getUserIdentifier());
    }

    public function testRemoveRole(): void
    {
        $user = new User();
        $role = new Role();
        $role->setLibelle('ADMIN');
        
        $user->addRole($role);
        $this->assertTrue($user->hasRole($role));
        
        $user->removeRole($role);
        $this->assertFalse($user->hasRole($role));
    }

    public function testUserValidation(): void
    {
        $user = new User();
        
        // Test avec username vide
        $this->assertNull($user->getUsername());
        
        // Test avec password vide
        $this->assertNull($user->getPassword());
    }
}