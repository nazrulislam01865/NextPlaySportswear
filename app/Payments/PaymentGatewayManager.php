<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\PaymentGatewayException;
use Illuminate\Contracts\Container\Container;

final class PaymentGatewayManager
{
    public function __construct(private readonly Container $container)
    {
    }

    public function gateway(string $code): PaymentGateway
    {
        $code = strtolower(trim($code));
        $driver = config("payments.gateways.{$code}.driver");

        if (! is_string($driver) || $driver === '') {
            throw new PaymentGatewayException("Payment gateway [{$code}] is not registered.");
        }

        $gateway = $this->container->make($driver);

        if (! $gateway instanceof PaymentGateway) {
            throw new PaymentGatewayException("Payment gateway [{$code}] does not implement the payment gateway contract.");
        }

        return $gateway;
    }

    public function registeredProviders(): array
    {
        return array_keys((array) config('payments.gateways', []));
    }

    public function isAvailable(string $code): bool
    {
        try {
            return $this->gateway($code)->configured();
        } catch (PaymentGatewayException) {
            return false;
        }
    }

    public function statuses(): array
    {
        $statuses = [];

        foreach ($this->registeredProviders() as $provider) {
            $statuses[$provider] = [
                'registered' => true,
                'configured' => $this->isAvailable($provider),
            ];
        }

        return $statuses;
    }
}
