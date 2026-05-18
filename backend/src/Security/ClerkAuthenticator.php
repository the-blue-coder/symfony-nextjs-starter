<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ClerkAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private CacheInterface $cache,
        #[Autowire(env: 'CLERK_JWKS_URI')]
        private string $clerkJwksUri,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $header = $request->headers->get('Authorization', '');

        return str_starts_with($header, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr($request->headers->get('Authorization', ''), 7);

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('No token provided.');
        }

        try {
            $jwks = $this->fetchJwks();
            $keySet = JWK::parseKeySet($jwks);
            $decoded = JWT::decode($token, $keySet);
        } catch (ExpiredException) {
            throw new CustomUserMessageAuthenticationException('Token has expired.');
        } catch (SignatureInvalidException) {
            throw new CustomUserMessageAuthenticationException('Invalid token signature.');
        } catch (\Exception) {
            throw new CustomUserMessageAuthenticationException('Invalid token.');
        }

        $clerkUserId = $decoded->sub ?? null;

        if (!$clerkUserId) {
            throw new CustomUserMessageAuthenticationException('Invalid token claims.');
        }

        return new SelfValidatingPassport(
            new UserBadge($clerkUserId, function (string $clerkUserId) use ($decoded): User {
                $user = $this->userRepository->findByClerkUserId($clerkUserId);

                if (!$user) {
                    $user = new User();
                    $user->setClerkUserId($clerkUserId);
                    $user->setEmail($decoded->email ?? "{$clerkUserId}@clerk.local");
                    $this->em->persist($user);
                    $this->em->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function fetchJwks(): array
    {
        return $this->cache->get('clerk_jwks', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            $response = file_get_contents($this->clerkJwksUri);
            if ($response === false) {
                throw new \RuntimeException('Failed to fetch Clerk JWKS.');
            }

            return json_decode($response, true);
        });
    }
}
