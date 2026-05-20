<?php

declare(strict_types=1);

namespace App\Controller\Webhook;

use App\Service\BackupService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class BackupController
{
	public function __construct(
		private readonly BackupService $backupService,
		#[Autowire(env: 'AWS_S3_BACKUP_WEBHOOK_SECRET')]
		private readonly string $webhookSecret,
	) {
	}

	#[Route('/api/webhook/backup', name: 'app_webhook_backup', methods: ['POST'])]
	public function __invoke(Request $request): JsonResponse
	{
		if (!hash_equals($this->webhookSecret, $request->headers->get('X-Backup-Secret', ''))) {
			return new JsonResponse([
				'message' => 'Unauthorized.',
			], Response::HTTP_UNAUTHORIZED);
		}

		try {
			$result = $this->backupService->run();
		} catch (Throwable $e) {
			return new JsonResponse([
				'message' => $e->getMessage(),
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}

		return new JsonResponse([
			'message' => 'Backup uploaded',
			'file' => $result['file'],
			'deleted' => $result['deleted'],
		]);
	}
}
