<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionFactory;

class LoginAttemptService
{
    private const MAX_ATTEMPTS = 5;
    private $session;

    public function __construct(SessionFactory $sessionFactory)
    {
        $this->session = $sessionFactory->createSession();
    }

    public function incrementAttempts(): void
    {
        $attempts = $this->session->get('login_attempts', 0);
        $this->session->set('login_attempts', ++$attempts);
    }

    public function resetAttempts(): void
    {
        $this->session->remove('login_attempts');
    }

    public function getAttempts(): int
    {
        return $this->session->get('login_attempts', 0);
    }

    public function isCaptchaRequired(): bool
    {
        return $this->getAttempts() >= self::MAX_ATTEMPTS;
    }
}