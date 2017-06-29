<?php

namespace Workshop\Domain\Command;

use Prooph\Common\Messaging\Command;

class BlockCard extends Command
{
    /**
     * @var string
     */
    private $cardId;

    private function __construct(string $cardId)
    {
        $this->init();

        $this->cardId = $cardId;
    }

    public static function from(string $cardId)
    {
        return new self($cardId);
    }

    public function cardId()
    {
        return $this->cardId;
    }

    public function payload()
    {
        return [
        ];
    }

    protected function setPayload(array $payload)
    {
    }
}