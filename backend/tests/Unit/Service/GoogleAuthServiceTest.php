<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GoogleAuthService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GoogleAuthServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    private GoogleAuthService $service;

    private const CLIENT_ID = 'test-client-id';

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new GoogleAuthService(
            $this->httpClient,
            $this->userRepository,
            $this->em,
            self::CLIENT_ID,
        );
    }

    private function mockTokenInfoResponse(array $payload, int $statusCode = 200): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('toArray')->willReturn($payload);

        $this->httpClient->method('request')->willReturn($response);
    }

    public function testAuthenticateCreatesNewUserForUnknownGoogleId(): void
    {
        $this->mockTokenInfoResponse([
            'sub' => 'google-123',
            'email' => 'new@example.com',
            'aud' => self::CLIENT_ID,
        ]);

        $this->userRepository->method('findByGoogleId')->willReturn(null);
        $this->userRepository->method('findByEmail')->willReturn(null);

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $user = $this->service->authenticateWithIdToken('id-token');

        $this->assertSame('new@example.com', $user->getEmail());
        $this->assertSame('google-123', $user->getGoogleId());
    }

    public function testAuthenticateReturnsExistingUserByGoogleId(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');

        $this->mockTokenInfoResponse([
            'sub' => 'google-456',
            'email' => 'existing@example.com',
            'aud' => self::CLIENT_ID,
        ]);

        $this->userRepository->method('findByGoogleId')->willReturn($existingUser);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $user = $this->service->authenticateWithIdToken('id-token');

        $this->assertSame($existingUser, $user);
    }

    public function testAuthenticateLinksGoogleIdToExistingEmailUser(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');

        $this->mockTokenInfoResponse([
            'sub' => 'google-789',
            'email' => 'existing@example.com',
            'aud' => self::CLIENT_ID,
        ]);

        $this->userRepository->method('findByGoogleId')->willReturn(null);
        $this->userRepository->method('findByEmail')->willReturn($existingUser);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $user = $this->service->authenticateWithIdToken('id-token');

        $this->assertSame($existingUser, $user);
        $this->assertSame('google-789', $user->getGoogleId());
    }

    public function testAuthenticateThrowsForNon200Response(): void
    {
        $this->mockTokenInfoResponse([], 400);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Google ID token.');

        $this->service->authenticateWithIdToken('bad-token');
    }

    public function testAuthenticateThrowsForAudienceMismatch(): void
    {
        $this->mockTokenInfoResponse([
            'sub' => 'google-123',
            'email' => 'user@example.com',
            'aud' => 'wrong-client-id',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Google token audience mismatch.');

        $this->service->authenticateWithIdToken('id-token');
    }

    public function testAuthenticateThrowsForMissingEmail(): void
    {
        $this->mockTokenInfoResponse([
            'sub' => 'google-123',
            'aud' => self::CLIENT_ID,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Google token missing email.');

        $this->service->authenticateWithIdToken('id-token');
    }
}
