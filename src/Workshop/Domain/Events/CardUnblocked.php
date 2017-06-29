<?php

namespace Workshop\Domain\Events;

use Prooph\EventSourcing\AggregateChanged;
use Rhumsaa\Uuid\Uuid;

class CardUnblocked extends AggregateChanged
{
    public static function from(string $id)
    {
        return self::occur(
            (string)$id
        );
    }
}