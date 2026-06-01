---
description: "Set up the database backup infrastructure (pg_dump → S3 + email alert) if not yet present"
---

Set up the complete backup feature on a project that does not have it yet. The feature adds a webhook-triggered `pg_dump` → S3 upload pipeline with email alert on failure.

---

## Step 1 - Check if already configured

Look for `backend/src/Controller/Webhook/BackupController.php`.

If it exists, stop and tell the user:
> Backup is already configured on this project. Nothing to do.

---

## Step 2 - Audit what is missing

Check each item below and build a list of what needs to be added:

**Composer packages** — read `backend/composer.json`:
- `aws/aws-sdk-php`
- `symfony/amazon-mailer`
- `symfony/process`

**PHP files** — check if each exists:
- `backend/src/Service/EmailService.php`
- `backend/src/Service/BackupService.php`
- `backend/src/Controller/Webhook/BackupController.php`

**Symfony config** — check if exists:
- `backend/config/packages/mailer.yaml`

**Env vars** — check `backend/.env` for each key:
- `AWS_SES_ACCESS_KEY_ID`
- `AWS_SES_SECRET_ACCESS_KEY`
- `MAILER_DSN`
- `MAILER_FROM`
- `AWS_S3_BACKUP_ACCESS_KEY_ID`
- `AWS_S3_BACKUP_SECRET_ACCESS_KEY`
- `MAILER_FROM`
- `AWS_S3_BACKUP_BUCKET`
- `AWS_S3_BACKUP_KEEP_LAST`
- `AWS_S3_BACKUP_WEBHOOK_SECRET`

Also check `backend/.env.example` for the same keys.

Read `backend/config/packages/security.yaml` to confirm whether `^/api/webhook` is already public. Note if a security change is needed.

---

## Step 3 - Create missing PHP files

Create each file that does not yet exist, using the exact content below.

### `backend/src/Service/EmailService.php`

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Throwable;

class EmailService
{
	public function __construct(
		private readonly MailerInterface $mailer,
		#[Autowire(env: 'MAILER_FROM')]
		private readonly string $mailerFrom,
		#[Autowire('%app.name%')]
		private readonly string $appName,
	) {}

	public function sendBackupError(string $subject, string $detail): void
	{
		try {
			$email = (new Email())
				->from($this->mailerFrom)
				->to($this->mailerFrom)
				->subject("[{$this->appName}] Backup error: {$subject}")
				->text($detail)
			;
			$this->mailer->send($email);
		} catch (Throwable) {
			// Email failure must never mask the original error
		}
	}
}
```

> Note: `%app.name%` must be defined in `config/services.yaml` as `app.name: '%env(APP_NAME)%'`. Verify it exists; add it if missing.

### `backend/src/Service/BackupService.php`

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

class BackupService
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
	) {
		$parts = explode('/', $awsBucket, 2);
		$this->s3Bucket = $parts[0];
		$this->s3Prefix = isset($parts[1]) ? rtrim($parts[1], '/') . '/' : '';
	}

	/**
	 * @return array{file: string, deleted: string[]}
	 *
	 * @throws ProcessFailedException
	 * @throws Throwable
	 */
	public function run(): array
	{
		$tmpPath = $this->dump();
		$s3 = $this->buildS3Client();
		$s3Key = $this->upload($s3, $tmpPath);
		$deleted = $this->pruneOldBackups($s3);

		return ['file' => $s3Key, 'deleted' => $deleted];
	}

	private function dump(): string
	{
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
		$process->setEnv(['PGPASSWORD' => $pass]);
		$process->setTimeout(120);

		try {
			$process->mustRun();
		} catch (ProcessFailedException $e) {
			$this->emailService->sendBackupError('pg_dump failed', $e->getMessage());
			throw $e;
		}

		return $tmpPath;
	}

	private function upload(S3Client $s3, string $tmpPath): string
	{
		$filename = basename($tmpPath);
		$s3Key = $this->s3Prefix . $filename;

		try {
			$s3->putObject([
				'Bucket' => $this->s3Bucket,
				'Key' => $s3Key,
				'SourceFile' => $tmpPath,
			]);
		} catch (Throwable $e) {
			$this->emailService->sendBackupError('S3 upload failed', $e->getMessage());
			throw $e;
		} finally {
			@unlink($tmpPath);
		}

		return $s3Key;
	}

	/** @return string[] */
	private function pruneOldBackups(S3Client $s3): array
	{
		$result = $s3->listObjectsV2([
			'Bucket' => $this->s3Bucket,
			'Prefix' => $this->s3Prefix,
		]);
		$objects = $result['Contents'] ?? [];

		if (count($objects) <= $this->awsKeepLast) {
			return [];
		}

		usort($objects, fn ($a, $b) => strcmp($a['Key'], $b['Key']));

		$toDelete = array_slice($objects, 0, count($objects) - $this->awsKeepLast);
		$deleteKeys = array_map(fn ($o) => ['Key' => $o['Key']], $toDelete);

		$s3->deleteObjects([
			'Bucket' => $this->s3Bucket,
			'Delete' => ['Objects' => $deleteKeys],
		]);

		return array_column($toDelete, 'Key');
	}

	private function buildS3Client(): S3Client
	{
		return new S3Client([
			'version' => 'latest',
			'region' => $this->awsRegion,
			'credentials' => [
				'key' => $this->awsKey,
				'secret' => $this->awsSecret,
			],
		]);
	}
}
```

### `backend/src/Controller/Webhook/BackupController.php`

```php
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
```

### `backend/config/packages/mailer.yaml` (create only if missing)

```yaml
framework:
	mailer:
		dsn: '%env(MAILER_DSN)%'
```

---

## Step 4 - Update security.yaml (if needed)

Check `backend/config/packages/security.yaml`.

The `^/api/webhook` pattern must be public (no JWT required). If it is not already, add:

```yaml
firewalls:
    webhook:
        pattern: ^/api/webhook
        security: false

access_control:
    - { path: ^/api/webhook, roles: PUBLIC_ACCESS }
```

Place the `webhook` firewall before the `api` firewall, and the `^/api/webhook` access rule before `^/api`.

---

## Step 5 - Update composer.json

For each of these packages, check if it is already in `backend/composer.json`. Add any that are missing to the `require` section:

```json
"aws/aws-sdk-php": "^3.0",
"symfony/amazon-mailer": "8.1.*",
"symfony/process": "8.1.*"
```

Keep `sort-packages: true` order (alphabetical within the Symfony group).

---

## Step 6 - Update env files

For each missing key, append it to `backend/.env` under the relevant section comment. Use placeholder values for secrets the user must fill in.

Keys and their sections:

```
# AWS SES
AWS_SES_ACCESS_KEY_ID=
AWS_SES_SECRET_ACCESS_KEY=

# Mailer
MAILER_DSN=ses+https://ACCESS_KEY:URL_ENCODED_SECRET@default?region=us-east-2
MAILER_FROM=contact@example.com

# AWS S3 Backup
AWS_S3_BACKUP_ACCESS_KEY_ID=
AWS_S3_BACKUP_SECRET_ACCESS_KEY=
AWS_S3_BACKUP_BUCKET=my-bucket/backups/db/my-project
AWS_S3_BACKUP_KEEP_LAST=4
AWS_S3_BACKUP_WEBHOOK_SECRET=CHANGE_ME
```

Do the same for `backend/.env.example`, using empty values or safe placeholder strings (never real credentials).

---

## Step 7 - Install composer packages

Run from `backend/`:

```bash
docker compose run --no-deps --rm backend composer require aws/aws-sdk-php "symfony/amazon-mailer:8.1.*" "symfony/process:8.1.*" --no-interaction
```

This updates `composer.lock` via the volume mount. If Docker is not running, tell the user to run this command manually before pushing.

---

## Step 8 - Check Dockerfile

Open `backend/Dockerfile` and confirm that `postgresql-client` is present in the `apk add` block of the `base` stage. Without it, `pg_dump` is not found at runtime inside the Alpine container.

If it is missing, add it:

```dockerfile
RUN apk add --no-cache \
    postgresql-dev \
    postgresql-client \
    ...
```

---

## Step 9 - Report

Tell the user:

- Which files were created.
- Which env vars were added (keys only, never values).
- Whether `composer require` ran successfully or needs to be run manually.
- Then show this checklist:

---

**Next steps:**

1. Fill in the real values for `AWS_S3_BACKUP_*`, `AWS_SES_*`, `MAILER_DSN`, and `MAILER_FROM` in `backend/.env`.
2. **Duplicate the n8n workflow** — go to https://n8n.madainsight.com/workflow/PWe4ZS7OlR3KzoDa, duplicate it, then:
   - Tag it with the app name (e.g. `Cashpoint`)
   - Move it into the folder `Personal > [AppName]`
   - Update the HTTP request node URL and `X-Backup-Secret` header to match this project
3. Redeploy the backend (`docker compose build && docker compose up -d`).
