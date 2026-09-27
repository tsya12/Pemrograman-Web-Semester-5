<?php

declare(strict_types=1);

class Transaction
{
    public function __construct(
        private string $id,
        private string $type,
        private float $amount,
    )

    public function getId(): string 
    {
        return $this->id;
    }

    public function getType(): string 
    {
        return $this->type;
    }

    public function getAmount(): float 
    {
        return $this->amount;
    }

    /**
     * @throws RuntimeException        jika saldo tidak mencukupi
     * @throws InvalidArgumentException jika jenis transaksi tidak dikenal
     */
    public function process(): void 
    {
        $balance = (float) ($_SESSION['balance'] ?? 0.0);

        $_SESSION['balance'] = match ($this->type) {
            'deposit' => round($balance + $this->amount, 2),
            'withdraw' => $balance >= $this->amount 
                ? round($balance - $this->amount, 2)
                : throw new RuntimeException('Saldo tidak mencukupi untuk penarikan.'),
            default => throw new InvalidArgumentException('Jenis transaksi tidak valid.'),
        };
    }

    /**
     * @return array{id: string, type: string, amount: float, time: string}
     */

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'type'  => $this->type,
            'amount'=> $this->amount,
            'time'  -> date('Y-m-d H:i:s'),
        ];
    }
}