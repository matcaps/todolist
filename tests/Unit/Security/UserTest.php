<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Exception\AccountCreationException;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccountStatusException;

class UserTest extends TestCase
{
    private const MIN_AGE = 18;

    private DateTimeImmutable $invalidDate;

    private DateTimeImmutable $validDate;

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
}
