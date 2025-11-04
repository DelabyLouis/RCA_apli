<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\AuditTrailService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Bundle\SecurityBundle\Security;

class AuditTrailListener
{
    public function __construct(
        private AuditTrailService $auditService,
        private Security $security
    ) {}

    /**
     * Capture les connexions réussies
     */
    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        /** @var \App\Entity\User|null $user */
        $user = $event->getUser();
        
        $this->auditService->logAction(
            AuditTrailService::ACTION_LOGIN,
            'User',
            $user ? $user->getIdUser() : null,
            [
                'username' => $user ? $user->getUsername() : 'unknown',
                'authentication_method' => 'form_login'
            ],
            AuditTrailService::SEVERITY_INFO
        );
    }

    /**
     * Capture les déconnexions
     */
    #[AsEventListener(event: LogoutEvent::class)]
    public function onLogout(LogoutEvent $event): void
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        
        $this->auditService->logAction(
            AuditTrailService::ACTION_LOGOUT,
            'User',
            $user ? $user->getIdUser() : null,
            [
                'username' => $user ? $user->getUsername() : 'unknown'
            ],
            AuditTrailService::SEVERITY_INFO
        );
    }

    /**
     * Capture les accès aux pages sensibles
     */
    #[AsEventListener(event: KernelEvents::CONTROLLER)]
    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        
        // Pages sensibles à auditer
        $sensitiveRoutes = [
            'app_my_data',
            'app_export_data', 
            'app_exercise_rights',
            'app_personne_show',
            'app_personne_edit',
            'app_user_show',
            'app_user_edit'
        ];
        
        if (in_array($route, $sensitiveRoutes) && $this->security->getUser()) {
            $this->auditService->logAction(
                'access_sensitive_page',
                'Route',
                null,
                [
                    'route_name' => $route,
                    'controller' => $request->attributes->get('_controller'),
                    'method' => $request->getMethod()
                ],
                AuditTrailService::SEVERITY_WARNING
            );
        }
    }

    /**
     * Capture les exports de données et autres actions critiques
     */
    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $route = $request->attributes->get('_route');
        
        // Auditer les exports de données
        if ($route === 'app_export_data' && $response->isSuccessful()) {
            $format = $request->attributes->get('format');
            
            $this->auditService->logAction(
                AuditTrailService::ACTION_EXPORT,
                'PersonalData',
                null,
                [
                    'export_format' => $format,
                    'file_size' => $response->headers->get('Content-Length', 'unknown'),
                    'success' => true
                ],
                AuditTrailService::SEVERITY_CRITICAL
            );
        }
    }
}