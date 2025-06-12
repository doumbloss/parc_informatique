<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\HistoriqueLoggerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

class SecurityEventListener implements EventSubscriberInterface
{
    private HistoriqueLoggerService $historiqueLogger;

    public function __construct(HistoriqueLoggerService $historiqueLogger)
    {
        $this->historiqueLogger = $historiqueLogger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        if ($user instanceof User) {
            $this->historiqueLogger->logAction(
                'LOGIN_SUCCESS',
                'Connexion réussie',
                $user
            );

            // Log supplémentaire avec détails de la session
            $request = $event->getRequest();
            $this->historiqueLogger->logAction(
                'SESSION_START',
                'Nouvelle session utilisateur',
                $user,
                null,
                [
                    'session_id' => $request->getSession()->getId(),
                    'remember_me' => $request->cookies->has('REMEMBERME'),
                    'user_agent' => $request->headers->get('User-Agent'),
                ]
            );
        }
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();
        $request = $event->getRequest();
        
        // Récupérer l'email depuis la requête
        $email = $request->request->get('email') ?? $request->request->get('_username') ?? 'inconnu';
        
        $failureReason = $this->getFailureReason($exception);
        
        $this->historiqueLogger->logLoginAttempt($email, false, $failureReason);

        // Vérifier s'il y a trop de tentatives échouées
        $this->checkForBruteForceAttempts($email, $request->getClientIp());
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $user = $token?->getUser();

        if ($user instanceof User) {
            $this->historiqueLogger->logAction(
                'LOGOUT',
                'Déconnexion utilisateur',
                $user
            );
        }
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        if ($user instanceof User) {
            // Mettre à jour la dernière connexion
            $user->setLastLoginAt(new \DateTime());
            // Note: Il faudrait persister cette information si vous ajoutez ce champ à l'entité User
        }
    }

    private function getFailureReason(\Throwable $exception): string
    {
        $className = get_class($exception);
        
        return match ($className) {
            'Symfony\Component\Security\Core\Exception\BadCredentialsException' => 'Mot de passe incorrect',
            'Symfony\Component\Security\Core\Exception\UserNotFoundException' => 'Utilisateur non trouvé',
            'Symfony\Component\Security\Core\Exception\AccountExpiredException' => 'Compte expiré',
            'Symfony\Component\Security\Core\Exception\CredentialsExpiredException' => 'Identifiants expirés',
            'Symfony\Component\Security\Core\Exception\DisabledException' => 'Compte désactivé',
            'Symfony\Component\Security\Core\Exception\LockedException' => 'Compte verrouillé',
            default => 'Erreur d\'authentification'
        };
    }

    private function checkForBruteForceAttempts(string $email, ?string $ipAddress): void
    {
        // Récupérer les tentatives récentes pour cet email
        $recentAttempts = $this->getRecentFailedAttempts($email, $ipAddress);
        
        // Si plus de 5 tentatives en 15 minutes, logger comme activité suspecte
        if (count($recentAttempts) >= 5) {
            $this->historiqueLogger->logAction(
                'SECURITY_ALERT',
                "Tentatives de force brute détectées pour {$email}",
                null,
                null,
                [
                    'email' => $email,
                    'ip_address' => $ipAddress,
                    'attempt_count' => count($recentAttempts),
                    'severity' => 'HIGH'
                ]
            );
        }
    }

    private function getRecentFailedAttempts(string $email, ?string $ipAddress): array
    {
        // Cette méthode devrait interroger les logs récents
        // Pour l'exemple, on retourne un tableau vide
        // Dans une vraie implémentation, il faudrait faire une requête en base
        return [];
    }
}

// Event Listener pour les changements de données sensibles
class UserDataChangeListener implements EventSubscriberInterface
{
    private HistoriqueLoggerService $historiqueLogger;

    public function __construct(HistoriqueLoggerService $historiqueLogger)
    {
        $this->historiqueLogger = $historiqueLogger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }

    public function onKernelController($event): void
    {
        $controller = $event->getController();
        
        // Si c'est un array (Controller + method)
        if (is_array($controller)) {
            $controllerObject = $controller[0];
            $method = $controller[1];
            
            // Logger l'accès aux sections sensibles
            if ($this->isSensitiveController($controllerObject)) {
                $this->logControllerAccess($controllerObject, $method, $event->getRequest());
            }
        }
    }

    private function isSensitiveController($controller): bool
    {
        $sensitiveControllers = [
            'App\Controller\UtilisateurController',
            'App\Controller\SecurityController',
            'App\Controller\AdminController'
        ];

        return in_array(get_class($controller), $sensitiveControllers);
    }

    private function logControllerAccess($controller, string $method, $request): void
    {
        $user = $request->hasSession() ? $request->getSession()->get('_security_main') : null;
        
        if ($user && method_exists($user, 'getUser')) {
            $actualUser = $user->getUser();
            
            if ($actualUser instanceof User) {
                $this->historiqueLogger->logAction(
                    'CONTROLLER_ACCESS',
                    sprintf('Accès à %s::%s', get_class($controller), $method),
                    $actualUser,
                    null,
                    [
                        'controller' => get_class($controller),
                        'method' => $method,
                        'route' => $request->attributes->get('_route')
                    ]
                );
            }
        }
    }
}