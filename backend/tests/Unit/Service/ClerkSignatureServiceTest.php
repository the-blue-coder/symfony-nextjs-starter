<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ClerkSignatureService;
use PHPUnit\Framework\TestCase;

class ClerkSignatureServiceTest extends TestCase
{
	private const SECRET_BYTES = 'clerk-webhook-secret-bytes';
	private const SVIX_ID = 'msg_2abcDEF';

	private ClerkSignatureService $clerkSignatureService;

	protected function setUp(): void
	{
		$this->clerkSignatureService = new ClerkSignatureService('whsec_' . base64_encode(self::SECRET_BYTES));
	}

	public function testItAcceptsAFreshlySignedPayload(): void
	{
		$timestamp = (string) time();
		$payload = '{"type":"user.created"}';

		$isValid = $this->clerkSignatureService->isValid(
			self::SVIX_ID,
			$timestamp,
			$this->sign($timestamp, $payload),
			$payload,
		);

		$this->assertTrue($isValid);
	}

	public function testItRejectsAGenuineSignatureReplayedAfterTheToleranceWindow(): void
	{
		$timestamp = (string) (time() - 600);
		$payload = '{"type":"user.deleted"}';

		// The signature below is genuine - only the age of the request makes it invalid.
		$isValid = $this->clerkSignatureService->isValid(
			self::SVIX_ID,
			$timestamp,
			$this->sign($timestamp, $payload),
			$payload,
		);

		$this->assertFalse($isValid);
	}

	public function testItRejectsATimestampTooFarInTheFuture(): void
	{
		$timestamp = (string) (time() + 600);
		$payload = '{"type":"user.created"}';

		$isValid = $this->clerkSignatureService->isValid(
			self::SVIX_ID,
			$timestamp,
			$this->sign($timestamp, $payload),
			$payload,
		);

		$this->assertFalse($isValid);
	}

	public function testItAcceptsATimestampAtTheEdgeOfTheToleranceWindow(): void
	{
		$timestamp = (string) (time() - 299);
		$payload = '{"type":"user.created"}';

		$isValid = $this->clerkSignatureService->isValid(
			self::SVIX_ID,
			$timestamp,
			$this->sign($timestamp, $payload),
			$payload,
		);

		$this->assertTrue($isValid);
	}

	public function testItRejectsATamperedPayload(): void
	{
		$timestamp = (string) time();
		$signature = $this->sign($timestamp, '{"type":"user.created"}');

		$isValid = $this->clerkSignatureService->isValid(
			self::SVIX_ID,
			$timestamp,
			$signature,
			'{"type":"user.deleted"}',
		);

		$this->assertFalse($isValid);
	}

	public function testItRejectsASignatureBoundToAnotherMessageId(): void
	{
		$timestamp = (string) time();
		$payload = '{"type":"user.created"}';

		$isValid = $this->clerkSignatureService->isValid(
			'msg_someoneElse',
			$timestamp,
			$this->sign($timestamp, $payload),
			$payload,
		);

		$this->assertFalse($isValid);
	}

	public function testItRejectsANonNumericTimestamp(): void
	{
		$payload = '{"type":"user.created"}';

		$isValid = $this->clerkSignatureService->isValid(
			self::SVIX_ID,
			'not-a-timestamp',
			$this->sign('not-a-timestamp', $payload),
			$payload,
		);

		$this->assertFalse($isValid);
	}

	/**
	 * @return iterable<string, array{string, string, string}>
	 */
	public static function missingHeaderProvider(): iterable
	{
		yield 'no svix-id' => ['', '1700000000', 'v1,whatever'];
		yield 'no svix-timestamp' => [self::SVIX_ID, '', 'v1,whatever'];
		yield 'no svix-signature' => [self::SVIX_ID, '1700000000', ''];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('missingHeaderProvider')]
	public function testItRejectsARequestWithAMissingHeader(string $svixId, string $timestamp, string $signature): void
	{
		$isValid = $this->clerkSignatureService->isValid($svixId, $timestamp, $signature, '{}');

		$this->assertFalse($isValid);
	}

	private function sign(string $timestamp, string $payload): string
	{
		$signature = base64_encode(hash_hmac('sha256', self::SVIX_ID . '.' . $timestamp . '.' . $payload, self::SECRET_BYTES, true));

		return "v1,{$signature}";
	}
}
