<?php

declare(strict_types=1);

namespace Chargily\EpayPlugin;

use Chargily\EpayPlugin\Action\ChargilyAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class ChargilyGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'chargily',
            'payum.factory_title' => 'Chargily Pay V2',
            'payum.action.capture' => new ChargilyAction(null, null),
        ]);

        if (false === $config['payum.api']) {
            $config['payum.default_options'] = [
                'secret_key' => '',
                'webhook_secret' => '',
                'api_base_url' => 'https://pay.chargily.net/test/api/v2',
                'description' => 'Order payment',
                'payment_method' => 'edahabia',
                'success_url' => '',
                'failure_url' => '',
                'locale' => 'fr',
            ];

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['secret_key', 'api_base_url', 'success_url', 'failure_url'];
            $config['payum.api'] = static function (ArrayObject $config): array {
                $config->validateNotEmpty($config['payum.required_options']);

                return [
                    'secret_key' => $config['secret_key'],
                    'webhook_secret' => $config['webhook_secret'],
                    'api_base_url' => rtrim((string) $config['api_base_url'], '/'),
                    'description' => $config['description'],
                    'payment_method' => $config['payment_method'],
                    'success_url' => $config['success_url'],
                    'failure_url' => $config['failure_url'],
                    'locale' => $config['locale'],
                ];
            };
        }
    }
}
