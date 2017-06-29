<?php

namespace Workshop\Domain\Events;

use Prooph\EventSourcing\AggregateChanged;
use Rhumsaa\Uuid\Uuid;

class PrepaidCardRegistered extends AggregateChanged
{
    public static function from(Uuid $id, string $number, string $currency)
    {
        return self::occur(
            (string)$id,
            [
                'number' => $number,
                'currency' => $currency,
            ]
        );
    }

    public function number()
    {
        return $this->payload['number'];
    }

    public function currency()
    {
        return $this->payload['currency'];
    }
}