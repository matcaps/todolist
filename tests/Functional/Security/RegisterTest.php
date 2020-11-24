<?php

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegisterTest extends WebTestCase
{
    public function testRegisterPageDisplaysLoginLink()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $link = $crawler
            ->filter('a:contains("I have an account")') // find all links with the text "Greet"
            ->eq(0)
            ->link();

        $client->click($link);

        //dd($link->getNode());
        self::assertNotNull($link->getNode());
        self::assertInstanceOf(\DOMElement::class, $link->getNode());
        self::assertSame('http://localhost/login', $client->getCrawler()->getUri());
    }

    public function testRegistrationPageDisplaysFormSuccesFully()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains("Register !");
        self::assertSelectorExists("form[name='user_registration']");
        self::assertSelectorExists("#user_registration_email");
        self::assertSelectorExists("#user_registration_plainPassword_first");
        self::assertSelectorExists("#user_registration_plainPassword_second");
        self::assertSelectorExists("#user_registration_accepted");
        self::assertSelectorExists("button[type='submit']");
    }

    /**
     * @dataProvider registerUserEmailProvider
     */
    public function testUserRegisteringWithoutTickedConditions($username, $password, $birthDate)
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/register');

        $buttonCrawlerNode = $crawler->selectButton('Register');
        $form = $buttonCrawlerNode->form();

        $form['user_registration[email]'] = $username;
        $form['user_registration[plainPassword][first]'] = $password;
        $form['user_registration[plainPassword][second]'] = $password;
        $form['user_registration[birthDate]'] = $password;

        $client->submit($form);

        self::assertSame('http://localhost/register', $client->getCrawler()->getUri());
    }

    /**
     * @dataProvider registerUserEmailProvider
     */
    public function testUserRegisteringWithTickedConditions($username, $password, $birthDate)
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/register');

        $buttonCrawlerNode = $crawler->selectButton('Register');
        $form = $buttonCrawlerNode->form();

        $form['user_registration[email]'] = $username;
        $form['user_registration[plainPassword][first]'] = $password;
        $form['user_registration[plainPassword][second]'] = $password;
        $form['user_registration[birthDate]'] = $birthDate->format("Y-m-d");
        $form['user_registration[accepted]']->tick();

        $crawler = $client->submit($form);

        self::assertSame('http://localhost/login', $client->getCrawler()->getUri());
        self::assertSelectorExists(".alert");
        self::assertSelectorTextContains(".alert", "Please check your mailbox");
    }

    /**
     * @dataProvider registerUserEmailProvider
     */
    public function testRegisterRoutineIsWorking($username): User
    {
        $client = static::createClient();


        $userRepository = self::$container->get(UserRepository::class);
        /** @var User $testUser */
        $testUser = $userRepository->findOneByEmail($username);

        self::assertInstanceOf(User::class, $testUser);
        self::assertSame($username, $testUser->getEmail());
        self::assertFalse($testUser->hasValidAccount());
        self::assertTrue($testUser->hasActivationToken());
        self::assertInstanceOf(\DateTimeInterface::class, $testUser->getCreatedAt());
        self::assertInstanceOf(\DateTimeInterface::class, $testUser->getActivationLimitAt());
        //assert that an email is sent
        self::assertNotNull($testUser->getActivationToken());
        self::assertInstanceOf(DateTimeInterface::class, $testUser->getActivationLimitAt());


        return $testUser;
    }

    public function testActivationRoutineWithErrorToken()
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', 'http://localhost/validate/invalidToken');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider registerUserEmailProvider
     */
    public function testActivationRoutineWithValidToken($username): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $userRepository = self::$container->get(UserRepository::class);
        /** @var User $testUser */
        $testUser = $userRepository->findOneByEmail($username);
        $token = $testUser->getActivationToken();
        $crawler = $client->request('GET', "http://localhost/validate/{$token}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(".alert", "Your account is now validated, please sign in!");
    }

    public function registerUserEmailProvider(): ?\Generator
    {
        yield ["newunactivateduser@todo.list", "password", new DateTimeImmutable("1970-01-01 00:00:00")];
    }
}
