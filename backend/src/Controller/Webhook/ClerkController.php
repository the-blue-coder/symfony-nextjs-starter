<?php

declare(strict_types=1);

namespace App\Controller\Webhook;

use App\Service\ClerkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClerkController extends AbstractController
{
	public function __construct(
		private readonly ClerkService $clerkService,
		#[Autowire(env: 'CLERK_WEBHOOK_SECRET')]
		private readonly string $webhookSecret,
	) {}

	#[Route('/api/webhook/clerk', name: 'app_webhook_clerk', methods: ['POST'])]
	public function webhook(Request $request): JsonResponse
	{
		if (!$this->verifySignature($request)) {
			return $this->json([
				'message' => 'Invalid signature.',
			], Response::HTTP_UNAUTHORIZED);
		}

		$payload = json_decode($request->getContent(), true);
		$eventType = $payload['type'] ?? '';
		$data = $payload['data'] ?? [];

		$this->clerkService->handle($eventType, $data);

		return $this->json([
			'message' => 'OK',
		]);
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
