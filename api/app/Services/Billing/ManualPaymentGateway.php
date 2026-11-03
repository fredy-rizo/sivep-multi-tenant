<?php

namespace App\Services\Billing;

use App\Contracts\PaymentGateway;

/**
 * Gateway manual: la organización paga por transferencia/efectivo y un
 * administrador confirma. Sirve como implementación por defecto y como base
 * para conectar Wompi, Mercado Pago, Stripe, etc. sin tocar los controladores.
 */
class ManualPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function charge(array $payload): array
    {
        return [
            'status' => 'pending',
            'reference' => null,
            'message' => 'Pago registrado para confirmación manual.',
        ];
    }
}
