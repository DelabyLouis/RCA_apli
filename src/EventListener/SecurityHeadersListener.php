<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        
        // En-têtes de sécurité pour la production
        if ($_ENV['APP_ENV'] === 'prod') {
            // Protection contre le clickjacking
            $response->headers->set('X-Frame-Options', 'DENY');
            
            // Protection contre le sniffing MIME
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            
            // Protection XSS
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            
            // Politique de sécurité du contenu (CSP)
            $response->headers->set('Content-Security-Policy', 
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "img-src 'self' data: https:; " .
                "font-src 'self'; " .
                "connect-src 'self'; " .
                "frame-ancestors 'none';"
            );
            
            // Strict Transport Security (HSTS)
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            
            // Politique de référent
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            
            // Protection contre les attaques par permissions
            $response->headers->set('Permissions-Policy', 
                'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), speaker=()'
            );
        }
    }
}