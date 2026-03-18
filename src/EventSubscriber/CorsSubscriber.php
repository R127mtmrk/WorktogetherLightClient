<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

class CorsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // High priority so OPTIONS preflight is handled before security firewall
            RequestEvent::class => ['onKernelRequest', 100],
            ResponseEvent::class => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Only handle API paths
        if (str_starts_with($path, '/api')) {
            // Handle preflight
            if ($request->getMethod() === 'OPTIONS') {
                $origin = $request->headers->get('Origin');

                $response = new Response();
                $response->setStatusCode(Response::HTTP_OK);

                if ($origin) {
                    // Echo the origin for better CORS compatibility with webviews (Tauri, file://...)
                    $response->headers->set('Access-Control-Allow-Origin', $origin);
                    // Add Vary header so caches know the response depends on Origin
                    $response->headers->set('Vary', 'Origin');
                } else {
                    // Fallback to wildcard when no Origin header (server-to-server or native clients)
                    $response->headers->set('Access-Control-Allow-Origin', '*');
                }

                $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
                $response->headers->set('Access-Control-Max-Age', '3600');

                $event->setResponse($response);
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (str_starts_with($path, '/api')) {
            $response = $event->getResponse();

            $origin = $request->headers->get('Origin');
            if ($origin) {
                if (!$response->headers->has('Access-Control-Allow-Origin')) {
                    $response->headers->set('Access-Control-Allow-Origin', $origin);
                }
                // Ensure caches vary on Origin
                if ($response->headers->has('Vary')) {
                    $vary = $response->headers->get('Vary');
                    if (strpos($vary, 'Origin') === false) {
                        $response->headers->set('Vary', $vary . ', Origin');
                    }
                } else {
                    $response->headers->set('Vary', 'Origin');
                }
            } else {
                if (!$response->headers->has('Access-Control-Allow-Origin')) {
                    $response->headers->set('Access-Control-Allow-Origin', '*');
                }
            }

            if (!$response->headers->has('Access-Control-Allow-Methods')) {
                $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
            }
            if (!$response->headers->has('Access-Control-Allow-Headers')) {
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            }
        }
    }
}
