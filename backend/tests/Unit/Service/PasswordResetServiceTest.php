<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\PasswordResetService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private PasswordResetTokenRepository $tokenRepository;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private EmailService $emailService;
    private PasswordResetService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->emailService = $this->createMock(EmailService::class);

        $this->service = new PasswordResetService(
            $this->userRepository,
            $this->tokenRepository,
            $this->em,
            $this->passwordHasher,
            $this->emailService,
            'https://app.example.com',
        );
    }

    public function testRequestResetDoesNothingForUnknownEmail(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');
        $this->emailService->expects($this->never())->method('sendPasswordReset');

        $this->service->requestReset('unknown@example.com');
    }

    public function testRequestResetCreatesTokenAndSendsEmail(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $this->userRepository
            ->method('findByEmail')
            ->with('user@example.com')
            ->willReturn($user)
        ;

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(PasswordResetToken::class));
        $this->em->expects($this->once())->method('flush');

        $this->emailService
            ->expects($this->once())
            ->method('sendPasswordReset')
            ->with($user, $this->stringContains('https://app.example.com/reset-password?token='))
        ;

        $this->service->requestReset('user@example.com');
    }

    public function testResetPasswordThrowsForInvalidToken(): void
    {
        $this->tokenRepository->method('findValidToken')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or expired token.');

        $this->service->resetPassword('invalid-token', 'newpassword');
    }

    public function testResetPasswordUpdatesPasswordAndMarksTokenUsed(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $token = new PasswordResetToken();
        $token->setUser($user);
        $token->setToken(hash('sha256', 'valid-raw-token'));
        $token->setExpiresAt(new DateTimeImmutable('+1 hour'));

        $this->tokenRepository->method('findValidToken')->willReturn($token);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'newpassword')
            ->willReturn('hashed-newpassword')
        ;

        $this->em->expects($this->once())->method('flush');

        $this->service->resetPassword('valid-raw-token', 'newpassword');

        $this->assertSame('hashed-newpassword', $user->getPassword());
        $this->assertNotNull($token->getUsedAt());
    }
}
