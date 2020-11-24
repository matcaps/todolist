<?php

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeInterface;
use DOMElement;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function dd;

use const APPLICATION_ENV;

class LoginTest extends WebTestCase
{
    public function testThatLoginPageIsDisplayed()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains("Log in!");
        self::assertSelectorTextContains("h1", "Please sign in");
    }

    public function testLoginPageContainsLoginForm()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        self::assertSelectorTextContains('h1', 'Please sign in');
        self::assertSelectorExists('#inputEmail');
        self::assertSelectorExists('#inputPassword');
        self::assertSelectorExists('button[type=\'submit\']');
    }

    /**
     * @dataProvider succesfullyLoginProvider
     */
    public function testSuccesfullyLoginAndRedirect($username, $password, $expectedUrl)
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/login');

        $buttonCrawlerNode = $crawler->selectButton('Sign in');
        $form = $buttonCrawlerNode->form();

        $form['email'] = $username;
        $form['password'] = $password;

        $crawler = $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSame($expectedUrl, $crawler->getUri());
    }

    /**
     * @dataProvider errorLoginProvider
     */
    public function testFailLogin($username, $password, $errorMessage)
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/login');

        $buttonCrawlerNode = $crawler->selectButton('Sign in');
        $form = $buttonCrawlerNode->form();

        $form['email'] = $username;
        $form['password'] = $password;

        $crawler = $client->submit($form);

        self::assertSame('http://localhost/login', $crawler->getUri());
        self::assertSelectorExists('.alert');
        self::assertSelectorTextContains('.alert', $errorMessage);
    }

    public function testRegistrationLinkIsDisplayed()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $link = $crawler
            ->filter('a:contains("Register")') // find all links with the text "Greet"
            ->eq(0)
            ->link();

        self::assertNotNull($link);
    }

    public function testRegistrationLinkRedirectsToRegistration()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $link = $crawler
            ->filter('a:contains("Register")') // find all links with the text "Greet"
            ->eq(0)
            ->link();

        $client->click($link);

        self::assertSame('http://localhost/register', $client->getRequest()->getUri());
    }

    /**
     * @dataProvider succesfullyLoginProvider
     */
    public function testConnectedUserIsRedirectedFromLogin($username, $password, $url)
    {
        $client = static::createClient();
        $client->followRedirects();

        $userRepository = self::$container->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail($username);

        $client->loginUser($testUser);

        $client->request('GET', '/login');

        self::assertSame($url, $client->getRequest()->getUri());
    }

    /**
     * DataProviders
     * @return Generator
     */
    public function succesfullyLoginProvider()
    {
        yield ["user@todo.list", "password", 'http://localhost/profile'];
        yield ["admin@todo.list", "password", 'http://localhost/admin'];
    }

    public function errorLoginProvider()
    {
        yield ["inactive@todo.list", "password", 'Invalid credentials.'];
        yield ["user@todo.list", "password_error", 'Invalid credentials.'];
        yield ["usererror@todo.list", "password", 'Email could not be found.'];
        yield [null, "password", 'Email could not be found.'];
        yield ["user@todo.list", null, 'Invalid credentials.'];
    }
}
