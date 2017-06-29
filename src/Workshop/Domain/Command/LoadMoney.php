<?php

namespace Workshop\Domain\Command;

use Prooph\Common\Messaging\Command;

class LoadMoney extends Command
{
    /**
     * @var string
     */
    private $cardId;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    private function __construct(string $cardId, int $amount, string $currency)
    {
        $this->init();

        $this->cardId = $cardId;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public static function from(string $cardId, int $amount, string $currency)
    {
        return new self($cardId, $amount, $currency);
    }

    public function cardId()
    {
        return $this->cardId;
    }

    public function amount()
    {
        return $this->amount;
    }

    public function currency()
    {
        return $this->currency;
    }

    public function payload()
    {
        return [
            'cardId' => $this->cardId,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    protected function setPayload(array $payload)
    {
        $this->cardId = $payload['cardId'];
        $this->amount = $payload['amount'];
        $this->currency = $payload['currency'];
    }
}