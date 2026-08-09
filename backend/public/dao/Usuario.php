<?php

declare(strict_types=1);

class Usuario
{
    public function __construct(
        private readonly int $id,
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly bool $active,
        private readonly bool $emailConfirmed,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['first_name'],
            (string) $row['last_name'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (bool) $row['active'],
            (bool) $row['email_confirmed'],
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isEmailConfirmed(): bool
    {
        return $this->emailConfirmed;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}
