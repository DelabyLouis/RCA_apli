<?php

namespace App\EventListener;

use App\Service\PermissionService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(event: 'kernel.controller')]
class PermissionListener
{
    public function __construct(
        private PermissionService $permissionService,
        private RouterInterface $router
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        
        // Ignorer les routes internes de Symfony
        if (!$routeName || str_starts_with($routeName, '_')) {
            return;
        }

        // Routes toujours autorisées
        $alwaysAllowed = [
            'app_logout',
            'app_register', 
            '_wdt',
            '_profiler',
            '_preview_error'
        ];
        
        if (in_array($routeName, $alwaysAllowed)) {
            return;
        }

        // Vérifier la permission
        if (!$this->permissionService->hasAccess($routeName)) {
            throw new AccessDeniedHttpException(
                'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'
            );
        }
    }
}