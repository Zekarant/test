<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ResetPasswordFormType;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, UserProviderInterface $userProvider, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Générer un token unique
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setTokenExpirationDate(new \DateTime('+30 minutes'));

                // Enregistrer le token et la date d'expiration en base de données
                $entityManager->persist($user);
                $entityManager->flush();

                // Envoyer l'email avec le lien de réinitialisation
                $resetPasswordUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // Créer le contenu HTML de l'e-mail
                $htmlContent = '
                <div style="width: 100%; font-family: \'Poppins\', sans-serif; border: 1px solid #14141C; font-weight: bold; background-color: #14141C; color: #FAFAFA;">
                    <div style="text-align: center; text-transform: uppercase; font-size: 1.5em; padding: 20px; border-bottom: 1px solid #FF33F1;">
                        Réinitialiser mon mot de passe - Mangas\'Fan
                    </div>
                    <div style="padding: 20px;">
                        <p>Bonjour ' . htmlspecialchars($user->getUsername(), ENT_QUOTES, 'UTF-8') . ',</p>
                        <p>Vous venez de faire une demande de réinitialisation de mot de passe sur le site. Vous avez la possibilité de le changer à l\'aide du lien ci-dessous.</p>
                        <p>Attention, le lien n\'est valable que <u>30 minutes</u>. Passé ce délai, vous devrez reformuler une nouvelle demande.</p>
                        <p>Pour changer votre mot de passe correctement, veuillez respecter les points suivants :</p>
                        <ul>
                            <li>8 caractères minimum</li>
                            <li>Un caractère minuscule</li>
                            <li>Un caractère majuscule</li>
                            <li>Un chiffre</li>
                        </ul>
                        <p>Si vous rencontrez des difficultés lors de votre changement de mot de passe, n\'hésitez pas à contacter l\'équipe à l\'adresse suivante : <a href="mailto:contact@mangasfan.fr" style="color: #FF33F1;">contact@mangasfan.fr</a>.</p>
                        <div style="width: 70%; margin: auto; text-align: center;">
                            <a href="' . htmlspecialchars($resetPasswordUrl, ENT_QUOTES, 'UTF-8') . '" style="display: inline-block; border-radius: 20px; border: 1px solid #FF33F1; padding: 10px 20px; color: #FAFAFA; text-decoration: none; background-color: #FF33F1; font-weight: bold;">
                                Cliquer ici pour réinitialiser votre mot de passe
                            </a>
                        </div>
                        <p style="font-size: 0.9em; padding-top: 10px">Si le bouton ne marche pas, accéder à la réinitialiser de votre mot de passe via ce lien : <a href="' . htmlspecialchars($resetPasswordUrl, ENT_QUOTES, 'UTF-8') . '" style="color: #FF33F1;">' . htmlspecialchars($resetPasswordUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
                    </div>
                    <div style="background-color: #1C1C28; padding: 20px; text-align: center;">
                        © Mangas\'Fan ~ ' . date('Y') . ' ~ Developped by Zekarant, Nico and Sora
                    </div>
                </div>';
                // Créer le message e-mail
                $emailMessage = (new Email())
                    ->from(new Address('support@mangasfan.fr', 'Support de Mangas\'Fan'))
                    ->to($email)
                    ->subject('Réinitialisation de votre mot de passe - ' . $user->getUsername())
                    ->html($htmlContent);

                // Envoyer l'e-mail
                $mailer->send($emailMessage);


                $this->addFlash('success', 'Un email de réinitialisation de mot de passe a été envoyé.');
                return $this->redirectToRoute('app_home');
            }

            $this->addFlash('danger', 'Aucun utilisateur trouvé avec cet email.');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(Request $request, string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || new \DateTime() > $user->getTokenExpirationDate()) {
            $this->addFlash('danger', 'Le lien de réinitialisation est invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('plainPassword')->getData();
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            $user->setResetToken(null);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

}