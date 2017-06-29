<?php

namespace Workshop\Domain\Events;

use Money\Money;
use Prooph\EventSourcing\AggregateChanged;
use Rhumsaa\Uuid\Uuid;

class LimitChanged extends AggregateChanged
{
    public static function from(string $id, string $limit)
    {
        return self::occur(
            (string)$id,
            [
                'limit' => $limit,
            ]
        );
    }

    public function limit()
    {
        return $this->payload['limit'];
    }
}