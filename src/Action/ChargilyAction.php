<?php

declare(strict_types=1);

namespace Chargily\EpayPlugin\Action;

use App\Entity\Order\Order;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChargilyAction implements ApiAwareInterface, ActionInterface
{
    private array $api = [];

    public function __construct(
        private readonly ?OrderRepositoryInterface $orderRepository,
        private readonly ?HttpClientInterface $httpClient,
    ) {
    }

    public function setApi($api): void
    {
        if (!is_array($api)) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        if (null === $this->orderRepository || null === $this->httpClient) {
            throw new \LogicException('ChargilyAction dependencies are not configured.');
        }

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $orderId = $request->getFirstModel()->getId();

        /** @var Order|null $order */
        $order = $this->orderRepository->find($orderId);
        if (null === $order) {
            throw new \RuntimeException(sprintf('Order with id "%s" was not found.', (string) $orderId));
        }

        $payload = [
            'amount' => $order->getTotal(),
            'currency' => strtolower($order->getCurrencyCode()),
            'payment_method' => $this->api['payment_method'],
            'success_url' => $this->api['success_url'],
            'failure_url' => $this->api['failure_url'],
            'webhook_endpoint' => rtrim($this->api['failure_url'], '/') . '/chargily/response/' . $order->getNumber(),
            'description' => sprintf('%s (%s)', (string) $this->api['description'], $order->getNumber()),
            'locale' => $this->normalizeLocale((string) $order->getLocaleCode(), (string) $this->api['locale']),
            'metadata' => [
                'order_number' => $order->getNumber(),
            ],
        ];

        $response = $this->httpClient->request('POST', $this->api['api_base_url'] . '/checkouts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api['secret_key'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ])->toArray(false);

        $redirectUrl = $response['checkout_url'] ?? $this->api['failure_url'];
        $model['chargily_checkout_id'] = $response['id'] ?? null;

        throw new HttpRedirect($redirectUrl);
    }

    public function supports($request): bool
    {
        return $request instanceof Capture && $request->getModel() instanceof \ArrayObject;
    }

    private function normalizeLocale(?string $orderLocale, string $fallback): string
    {
        if (null === $orderLocale || '' === $orderLocale) {
            return $fallback;
        }

        $locale = strtolower(explode('_', $orderLocale)[0]);

        if (in_array($locale, ['ar', 'en', 'fr'], true)) {
            return $locale;
        }

        return $fallback;
    }
}
