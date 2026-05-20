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
