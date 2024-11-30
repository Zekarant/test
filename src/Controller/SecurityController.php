<?php
// src/Controller/SecurityController.php
namespace App\Controller;

use App\Service\LoginAttemptService;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    private $loginAttemptService;
    private $validator;

    public function __construct(LoginAttemptService $loginAttemptService, ValidatorInterface $validator)
    {
        $this->loginAttemptService = $loginAttemptService;
        $this->validator = $validator;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Check if reCAPTCHA is required
        $captchaRequired = $this->loginAttemptService->isCaptchaRequired();
        $recaptchaError = null;

        if ($captchaRequired) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $recaptcha = new Recaptcha3();
            $recaptcha->message = 'Invalid reCAPTCHA';

            $violations = $this->validator->validate($recaptchaResponse, $recaptcha);

            if (count($violations) > 0) {
                $recaptchaError = 'Veuillez vérifier le reCAPTCHA.';
            }
        }

        // Increment login attempts if there is an error
        if ($error || $recaptchaError) {
            $this->loginAttemptService->incrementAttempts();
        } else {
            $this->loginAttemptService->resetAttempts();
        }

        return $this->render('user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'captcha_required' => $captchaRequired,
            'recaptcha_site_key' => $_ENV['KARSER_RECAPTCHA3_SITE_KEY'],
            'recaptcha_error' => $recaptchaError,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}