<?php

namespace App\Contracts;

interface PaymentGateway
{
    public function name(): string;

    /**
     * Procesa un cobro. En el gateway manual la organización paga por
     * transferencia y un administrador confirma el pago, por eso el resultado
     * queda "pending" hasta la confirmación.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string, reference: ?string, message: string}
     */
    public function charge(array $payload): array;
}
