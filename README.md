# chargily-epay-sylius

Sylius plugin for **Chargily Pay V2**.

## Compatibility

- PHP **8.2+**
- Sylius **2.2+**
- Symfony **6.4 / 7.4** (through Sylius)

## Installation

```bash
composer require chargily/epay-sylius
```

Register the bundle in `config/bundles.php`:

```php
Chargily\EpayPlugin\ChargilyEpayPlugin::class => ['all' => true],
```

Import services in `config/services.yaml`:

```yaml
imports:
  - { resource: '@ChargilyEpayPlugin/Resources/config/services.yml' }
```

Import routes in `config/routes/sylius_shop.yaml`:

```yaml
sylius_shop_chargily:
  resource: '@ChargilyEpayPlugin/Resources/config/shop_routing.yml'
```

## Gateway configuration (Chargily Pay V2)

Configure these fields in your Sylius payment method:

- `secret_key`: Chargily API secret key.
- `webhook_secret`: webhook signature secret (recommended).
- `api_base_url`:
  - Test: `https://pay.chargily.net/test/api/v2`
  - Live: `https://pay.chargily.com/api/v2`
- `success_url`: redirect URL after successful payment.
- `failure_url`: redirect URL after failed/canceled payment.
- `description`: default checkout description.
- `payment_method`: `edahabia`, `cib`, or `chargily_app`.
- `locale`: `ar`, `en`, or `fr`.

## Webhook endpoint

The plugin exposes:

`POST /chargily/response/{OrderNumber}`

Use this endpoint as the checkout `webhook_endpoint` to update Sylius payment state.

## Notes

- This plugin now creates **V2 checkouts** using `/checkouts` endpoint and redirects to returned `checkout_url`.
- Webhook handler supports both legacy `invoice.status` and V2 `data.status` payloads.
