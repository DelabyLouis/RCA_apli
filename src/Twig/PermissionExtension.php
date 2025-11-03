<?php

namespace App\Twig;

use App\Service\PermissionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PermissionExtension extends AbstractExtension
{
    public function __construct(private PermissionService $permissionService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_access', [$this->permissionService, 'hasAccess']),
            new TwigFunction('has_minimum_level', [$this->permissionService, 'hasMinimumLevel']),
            new TwigFunction('user_max_level', [$this->permissionService, 'getUserMaxLevel']),
            new TwigFunction('user_permissions', [$this->permissionService, 'getUserPermissions']),
        ];
    }
}