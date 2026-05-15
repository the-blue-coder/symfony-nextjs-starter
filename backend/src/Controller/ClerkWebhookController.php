<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clerk')]
class ClerkWebhookController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        #[Autowire(env: 'CLERK_WEBHOOK_SECRET')]
        private string $webhookSecret,
    ) {
    }

    #[Route('/webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        if (!$this->verifySignature($request)) {
            return $this->json(['message' => 'Invalid signature.'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        $eventType = $payload['type'] ?? '';
        $data = $payload['data'] ?? [];

        match ($eventType) {
            'user.created' => $this->handleUserCreated($data),
            'user.updated' => $this->handleUserUpdated($data),
            'user.deleted' => $this->handleUserDeleted($data),
            default => null,
        };

        return $this->json(['message' => 'OK']);
    }

    private function handleUserCreated(array $data): void
    {
        $clerkUserId = $data['id'] ?? '';
        if (!$clerkUserId || $this->userRepository->findByClerkUserId($clerkUserId)) {
            return;
        }

        $user = new User();
        $user->setClerkUserId($clerkUserId);
        $user->setEmail($this->extractPrimaryEmail($data) ?? "{$clerkUserId}@clerk.local");

        $this->em->persist($user);
        $this->em->flush();
    }

    private function handleUserUpdated(array $data): void
    {
        $clerkUserId = $data['id'] ?? '';
        $user = $clerkUserId ? $this->userRepository->findByClerkUserId($clerkUserId) : null;
        if (!$user) {
            return;
        }

        $email = $this->extractPrimaryEmail($data);
        if ($email) {
            $user->setEmail($email);
        }

        $this->em->flush();
    }

    private function handleUserDeleted(array $data): void
    {
        $clerkUserId = $data['id'] ?? '';
        $user = $clerkUserId ? $this->userRepository->findByClerkUserId($clerkUserId) : null;
        if (!$user) {
            return;
        }

        $this->em->remove($user);
        $this->em->flush();
    }

    private function extractPrimaryEmail(array $data): ?string
    {
        $primaryEmailId = $data['primary_email_address_id'] ?? null;

        foreach ($data['email_addresses'] ?? [] as $emailData) {
            if ($emailData['id'] === $primaryEmailId) {
                return $emailData['email_address'] ?? null;
            }
        }

        return null;
    }

    private function verifySignature(Request $request): bool
    {
        $svixId = $request->headers->get('svix-id', '');
        $svixTimestamp = $request->headers->get('svix-timestamp', '');
        $svixSignature = $request->headers->get('svix-signature', '');

        if (!$svixId || !$svixTimestamp || !$svixSignature) {
            return false;
        }

        // Clerk webhook secret format: whsec_<base64-encoded-secret>
        $secretBytes = base64_decode(str_replace('whsec_', '', $this->webhookSecret));
        $toSign = "{$svixId}.{$svixTimestamp}." . $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $toSign, $secretBytes, true));

        foreach (explode(' ', $svixSignature) as $sig) {
            if (str_starts_with($sig, 'v1,') && hash_equals($expectedSignature, substr($sig, 3))) {
                return true;
            }
        }

        return false;
    }
}
