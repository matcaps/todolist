<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Uid\UuidV4;

use function in_array;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() === null) {
            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();
            // last username entered by the user
            $lastUsername = $authenticationUtils->getLastUsername();

            return $this->render(
                'security/login.html.twig',
                [
                    'last_username' => $lastUsername,
                    'error' => $error
                ]
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin');
        }

        if (in_array('ROLE_USER', $user->getRoles())) {
            return $this->redirectToRoute('profile');
        }

        throw new LogicException("Unattended Exception");
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordEncoderInterface $encoder,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $form = $this->createForm(UserRegistrationType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();
            $user->setPassword($encoder->encodePassword($user, $user->getPlainPassword()));

            $user->requestAccountActivation(new UuidV4());

            $em->persist($user);
            $em->flush();

            $message = (new Email())
                ->from("welcome@todo.list")
                ->to($user->getEmail())
                ->subject("Welcome by todo.list ! ")
                ->text(
                    $urlGenerator->generate(
                        'app_validate_account',
                        [
                            'token' => $user->getActivationToken()
                        ]
                    )
                );

            $mailer->send($message);

            $this->addFlash('success', "User {$user->getEmail()} is registered. Please check your mailbox");

            return $this->redirectToRoute("app_login");
        }

        return $this->render(
            'security/registration.html.twig',
            [
                'registrationForm' => $form->createView()
            ]
        );
    }

    /**
     * @Route("/validate/{token}", name="app_validate_account")
     */
    public function validateAccount(string $token, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->findOneBy(['activationToken' => $token]);
        if (null === $user) {
            $this->addFlash("danger", "Invalid token");
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $user->validateAccount();
        $em->persist($user);
        $em->flush();

        $this->addFlash("success", "Your account is now validated, please sign in!");
        return $this->redirectToRoute("app_login");
    }
}
