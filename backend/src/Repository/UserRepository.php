<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByClerkUserId(string $clerkUserId): ?User
    {
        return $this->findOneBy([
            'clerkUserId' => $clerkUserId,
        ]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy([
            'email' => $email,
        ]);
    }
}
