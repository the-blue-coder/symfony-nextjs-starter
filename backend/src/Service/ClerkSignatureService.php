<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Verifies the svix signature Clerk sends with every webhook.
 *
 * The ^/api/webhook firewall is `security: false`, so this check is the only
 * thing standing between the internet and the webhook handlers.
 */
class ClerkSignatureService
{
	/**
	 * How far a webhook timestamp may drift from now, in seconds. Without this
	 * window a captured request replays forever.
	 */
	private const TOLERANCE_SECONDS = 300;

	public function __construct(
		#[Autowire(env: 'CLERK_WEBHOOK_SECRET')]
		private readonly string $webhookSecret,
	) {}

	public function isValid(string $svixId, string $svixTimestamp, string $svixSignature, string $payload): bool
	{
		if (!$svixId || !$svixTimestamp || !$svixSignature) {
			return false;
		}

		if (!$this->isTimestampFresh($svixTimestamp)) {
			return false;
		}

		// Clerk webhook secret format: whsec_<base64-encoded-secret>
		$secretBytes = base64_decode(str_replace('whsec_', '', $this->webhookSecret));
		$toSign = "{$svixId}.{$svixTimestamp}.{$payload}";
		$expectedSignature = base64_encode(hash_hmac('sha256', $toSign, $secretBytes, true));

		foreach (explode(' ', $svixSignature) as $signature) {
			if (str_starts_with($signature, 'v1,') && hash_equals($expectedSignature, substr($signature, 3))) {
				return true;
			}
		}

		return false;
	}

	private function isTimestampFresh(string $svixTimestamp): bool
	{
		if (!ctype_digit($svixTimestamp)) {
			return false;
		}

		return abs(time() - (int) $svixTimestamp) <= self::TOLERANCE_SECONDS;
	}
}
