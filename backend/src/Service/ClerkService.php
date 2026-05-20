<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ClerkService
{
	public function __construct(
		private readonly UserRepository $userRepository,
		private readonly EntityManagerInterface $em,
	) {}

	public function handle(string $eventType, array $data): void
	{
		match ($eventType) {
			'user.created' => $this->handleUserCreated($data),
			'user.updated' => $this->handleUserUpdated($data),
			'user.deleted' => $this->handleUserDeleted($data),
			default        => null,
		};
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
}
