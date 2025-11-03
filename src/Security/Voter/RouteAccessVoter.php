<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\PermissionService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RouteAccessVoter extends Voter
{
    public const VIEW = 'route_access';

    public function __construct(
        private PermissionService $permissionService,
        private Security $security
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // $subject est le nom de la route
        return $this->permissionService->hasAccess($subject);
    }
}