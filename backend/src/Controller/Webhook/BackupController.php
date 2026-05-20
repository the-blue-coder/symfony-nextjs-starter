<?php

declare(strict_types=1);

namespace App\Controller\Webhook;

use App\Service\EmailService;
use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class BackupController
{
	private readonly string $s3Bucket;
	private readonly string $s3Prefix;

	public function __construct(
		private readonly EmailService $emailService,
		#[Autowire(env: 'AWS_S3_BACKUP_ACCESS_KEY_ID')]
		private readonly string $awsKey,
		#[Autowire(env: 'AWS_S3_BACKUP_SECRET_ACCESS_KEY')]
		private readonly string $awsSecret,
		#[Autowire(env: 'AWS_DEFAULT_REGION')]
		private readonly string $awsRegion,
		#[Autowire(env: 'AWS_S3_BACKUP_BUCKET')]
		string $awsBucket,
		#[Autowire(env: 'int:AWS_S3_BACKUP_KEEP_LAST')]
		private readonly int $awsKeepLast,
		#[Autowire(env: 'DATABASE_URL')]
		private readonly string $databaseUrl,
		#[Autowire(env: 'AWS_S3_BACKUP_WEBHOOK_SECRET')]
		private readonly string $webhookSecret,
	) {
		$parts = explode('/', $awsBucket, 2);
		$this->s3Bucket = $parts[0];
		$this->s3Prefix = isset($parts[1]) ? rtrim($parts[1], '/') . '/' : '';
	}

	#[Route('/api/webhook/backup', name: 'app_webhook_backup', methods: ['POST'])]
	public function __invoke(Request $request): JsonResponse
	{
		if (!hash_equals($this->webhookSecret, $request->headers->get('X-Backup-Secret', ''))) {
			return new JsonResponse([
				'message' => 'Unauthorized.',
			], Response::HTTP_UNAUTHORIZED);
		}

		$dsn = parse_url($this->databaseUrl);
		$host = $dsn['host'];
		$port = $dsn['port'] ?? 5432;
		$user = $dsn['user'];
		$pass = $dsn['pass'];
		$db = explode('?', ltrim($dsn['path'], '/'))[0];

		$filename = sprintf('backup_%s.sql', date('Y-m-d_H-i-s'));
		$tmpPath = sys_get_temp_dir() . '/' . $filename;

		$process = new Process([
			'pg_dump',
			'-h', $host,
			'-p', (string) $port,
			'-U', $user,
			'-d', $db,
			'-f', $tmpPath,
		]);
		$process->setEnv([
			'PGPASSWORD' => $pass,
		]);
		$process->setTimeout(120);

		try {
			$process->mustRun();
		} catch (ProcessFailedException $e) {
			$this->emailService->sendBackupError('pg_dump failed', $e->getMessage());

			return new JsonResponse([
				'message' => 'pg_dump failed: ' . $e->getMessage(),
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}

		$s3 = new S3Client([
			'version' => 'latest',
			'region' => $this->awsRegion,
			'credentials' => [
				'key' => $this->awsKey,
				'secret' => $this->awsSecret,
			],
		]);
		$s3Key = $this->s3Prefix . $filename;

		try {
			$s3->putObject([
				'Bucket' => $this->s3Bucket,
				'Key' => $s3Key,
				'SourceFile' => $tmpPath,
			]);
		} catch (Throwable $e) {
			$this->emailService->sendBackupError('S3 upload failed', $e->getMessage());

			return new JsonResponse([
				'message' => 'S3 upload failed: ' . $e->getMessage(),
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		} finally {
			@unlink($tmpPath);
		}

		$this->pruneOldBackups($s3);

		return new JsonResponse([
			'message' => 'Backup uploaded',
			'file' => $s3Key,
		]);
	}

	private function pruneOldBackups(S3Client $s3): void
	{
		$result = $s3->listObjectsV2([
			'Bucket' => $this->s3Bucket,
			'Prefix' => $this->s3Prefix,
		]);
		$objects = $result['Contents'] ?? [];

		if (count($objects) <= $this->awsKeepLast) {
			return;
		}

		usort($objects, fn ($a, $b) => strcmp($a['Key'], $b['Key']));

		$toDelete = array_slice($objects, 0, count($objects) - $this->awsKeepLast);
		$deleteKeys = array_map(
			fn ($o) => [
				'Key' => $o['Key'],
			],
			$toDelete,
		);

		$s3->deleteObjects([
			'Bucket' => $this->s3Bucket,
			'Delete' => [
				'Objects' => $deleteKeys,
			],
		]);
	}
}
