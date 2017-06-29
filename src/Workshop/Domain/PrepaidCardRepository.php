<?php

namespace Workshop\Domain;

use Rhumsaa\Uuid\Uuid;

interface PrepaidCardRepository
{
    public function get(Uuid $id): PrepaidCard;

    public function save(PrepaidCard $prepaidCard);
}