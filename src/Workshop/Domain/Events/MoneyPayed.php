<?php

namespace Workshop\Domain\Events;

use Prooph\EventSourcing\AggregateChanged;
use Rhumsaa\Uuid\Uuid;

class MoneyPayed extends AggregateChanged
{
    public static function from($id, int $amount, string $currency)
    {
        return self::occur(
            (string)$id,
            [
                'amount' => $amount,
                'currency' => $currency,
            ]
        );
    }

    public function amount()
    {
        return $this->payload['amount'];
    }

    public function currency()
    {
        return $this->payload['currency'];
    }
}