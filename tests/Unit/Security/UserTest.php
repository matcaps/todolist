<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Exception\AccountCreationException;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Throwable;

class UserTest extends WebTestCase
{
    private const MIN_AGE = 18;

    private DateTimeImmutable $invalidDate;

    private DateTimeImmutable $validDate;

    private function getKernel()
    {
        $kernel = self::bootKernel();
        $kernel->boot();

        return $kernel;
    }

    /**
     * @dataProvider validUserData
     */
    public function testUserhasMinimalRequiredAge($username, $date): void
    {
        $user = new User();
        $user->setEmail($username);
        $user->setBirthDate($date);

        self::assertInstanceOf(User::class, $user);
    }

    /**
     * @dataProvider invalidUserData
     */
    public function testUserhasNotMinimalRequiredAge($username, $date)
    {
        $this->expectException(AccountCreationException::class);

        $user = new User();
        $user->setEmail($username);
        $user->setBirthDate($date);
    }

    /**
     * @dataProvider provideInvalidEmailValues
     */
    public function testEmailProperty($email)
    {

        //$this->expectException(InvalidArgumentException::class);

        $user = new User();
        $user->setEmail($email);

        $kernel = $this->getKernel();
        $validator = $kernel->getContainer()->get('validator');

        $violationList = $validator->validate($user);

        //assert that there is minimum & violation
        self::assertGreaterThanOrEqual(1, count($violationList));
    }

    public function testActivationLimit()
    {

        $user = new User();

        self::assertNull($user->getActivationLimitAt());

        $user->requestAccountActivation(new UuidV4());

        self::assertInstanceOf(DateTimeInterface::class, $user->getActivationLimitAt());
    }


    //Providers

    public function validUserData()
    {
        $validIntervalYears = "P" . (1 + self::MIN_AGE) . "Y";

        $this->today = new DateTimeImmutable();
        $this->validDate = $this->today->sub(new DateInterval($validIntervalYears));

        yield ['oldEnoughUser@todo.list', $this->validDate];
    }

    public function invalidUserData()
    {
        $invalidIntervalYears = "P" . (self::MIN_AGE - 1) . "Y";

        $this->today = new DateTimeImmutable();
        $this->invalidDate = $this->today
            ->sub(new DateInterval($invalidIntervalYears))
            ->setTime(0, 0, 0);

        yield ['toYoungUser@todo.list', $this->invalidDate];
    }

    public function provideInvalidEmailValues()
    {
        yield ['ponpon.fr'];
        yield ['ponpon.fr@'];
        yield ['@ponpon.fr'];
        yield ['em ail@ponpon.fr'];
        yield [''];
        yield [' '];
        yield ['1234'];
    }
}
