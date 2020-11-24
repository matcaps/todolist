<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail("user@todo.list");
        $user->setBirthDate(new DateTimeImmutable("1970-01-01 00:00:00"));
        $user->setPassword($this->passwordEncoder->encodePassword($user, '$1234Abcd'));
        $user->validateAccount();
        $user->setRoles([]);

        $manager->persist($user);

        $user = new User();
        $user->setEmail("admin@todo.list");
        $user->setBirthDate(new DateTimeImmutable("1970-01-01 00:00:00"));
        $user->setPassword($this->passwordEncoder->encodePassword($user, '$1234Abcd'));
        $user->setRoles(['ROLE_ADMIN']);
        $user->validateAccount();
        $manager->persist($user);

        $user = new User();
        $user->setEmail("inactive@todo.list");
        $user->setBirthDate(new DateTimeImmutable("1970-01-01 00:00:00"));
        $user->setPassword($this->passwordEncoder->encodePassword($user, '$1234Abcd'));
        $user->setRoles([]);
        $manager->persist($user);

        $manager->flush();


        $manager->flush();
    }
}
