<?php

declare(strict_types=1);

namespace App\Controller\Webhook;

use App\Service\ClerkService;
use App\Service\ClerkSignatureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClerkController extends AbstractController
{
	public function __construct(
		private readonly ClerkService $clerkService,
		private readonly ClerkSignatureService $clerkSignatureService,
	) {}

	#[Route('/api/webhook/clerk', name: 'app_webhook_clerk', methods: ['POST'])]
	public function webhook(Request $request): JsonResponse
	{
		$isValid = $this->clerkSignatureService->isValid(
			$request->headers->get('svix-id', ''),
			$request->headers->get('svix-timestamp', ''),
			$request->headers->get('svix-signature', ''),
			$request->getContent(),
		);

		if (!$isValid) {
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
}
