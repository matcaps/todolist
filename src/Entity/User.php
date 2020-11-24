<?php

namespace App\Entity;

use App\Exception\AccountCreationException;
use App\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\Constraints as Assert;

use function dd;
use function in_array;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 */
class User implements UserInterface
{
    public const ACTIVATION_LIMIT_IN_DAYS = 2;
    public const MINIMUM_AGE_TO_CREATE_ACCOUNT = 18;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\Email(
     *     message="L'adresse email '{{ value }}' ne semble pas valide."
     * )
     * @Assert\NotBlank(
     *     message="L'adresse email '{{ value }}' ne peut pas être vide."
     * )
     */
    private string $email;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @ORM\Column(type="string")
     */
    private ?string $password;

    /**
     * @Assert\NotBlank
     * @Assert\Length(min=8)
     * @Assert\Regex(
     *     pattern="#.*^(?=.{8,20})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).*$#"
     * )
     */
    private ?string $plainPassword;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeInterface $createdAt = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isAccountValid = false;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $accountValidatedAt = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $activationToken;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $activationRequestedAt = null;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $birthDate;

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @codeCoverageIgnore
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string)$this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;// not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @return mixed
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param mixed $plainPassword
     */
    public function setPlainPassword($plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function hasValidAccount(): ?bool
    {
        return $this->isAccountValid === true;
    }

    public function validateAccount(): self
    {
        $this->isAccountValid = true;
        $this->accountValidatedAt = new DateTimeImmutable();

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getAccountValidatedAt(): ?\DateTimeImmutable
    {
        return $this->accountValidatedAt;
    }

    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setActivationToken(?string $activationToken): self
    {
        $this->activationToken = $activationToken;
        return $this;
    }

    public function hasActivationToken(): bool
    {
        return $this->getActivationToken() !== null;
    }

    public function getActivationLimitAt(): ?DateTimeInterface
    {
        if (null === $this->activationRequestedAt) {
            return null;
        }

        $limit = self::ACTIVATION_LIMIT_IN_DAYS;
        return $this->activationRequestedAt->add(
            new DateInterval("P{$limit}D")
        );
    }

    public function requestAccountActivation(UuidV4 $uuid): void
    {
        $this->activationRequestedAt = new DateTimeImmutable();
        $this->activationToken = $uuid;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): self
    {
        $limitBirthdate = (new DateTimeImmutable())
            ->setTime(0, 0, 0)
            ->sub(new DateInterval("P" . self::MINIMUM_AGE_TO_CREATE_ACCOUNT . "Y"));

        if ($birthDate > $limitBirthdate) {
            throw new AccountCreationException("User is not old enough to create an account");
        }

        $this->birthDate = $birthDate;

        return $this;
    }
}
