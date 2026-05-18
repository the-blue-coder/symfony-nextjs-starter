<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureService
{
    public function __construct(private HubInterface $hub) {}

    public function publish(string $topic, array $data): void
    {
        $update = new Update(
            $topic,
            json_encode($data, JSON_THROW_ON_ERROR),
        );

        $this->hub->publish($update);
    }
}
