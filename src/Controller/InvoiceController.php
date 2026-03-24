<?php

declare(strict_types=1);

namespace Chargily\EpayPlugin\Controller;

use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class InvoiceController extends AbstractController
{
    public function renderPdfAction(Request $request): Response
    {
        $number = (string) $request->attributes->get('OrderNumber');
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonResponse(['code' => 400, 'message' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        $gatewayConfig = $this->container->get('sylius.repository.gateway_config')->findOneBy(['factoryName' => 'chargily']);
        $webhookSecret = $gatewayConfig?->getConfig()['webhook_secret'] ?? null;

        if (is_string($webhookSecret) && '' !== $webhookSecret && !$this->isValidSignature($request, $webhookSecret)) {
            return new JsonResponse(['code' => 401, 'message' => 'Invalid webhook signature'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var object|null $order */
        $order = $this->container->get('sylius.repository.order')->findOneBy(['number' => $number]);
        if (null === $order) {
            return new JsonResponse(['code' => 404, 'message' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $status = $payload['data']['status'] ?? $payload['invoice']['status'] ?? null;
        if (!is_string($status)) {
            return new JsonResponse(['code' => 400, 'message' => 'Missing checkout status'], Response::HTTP_BAD_REQUEST);
        }

        /** @var PaymentInterface|null $payment */
        $payment = $order->getLastPayment();
        if (null === $payment) {
            return new JsonResponse(['code' => 400, 'message' => 'Order has no payment'], Response::HTTP_BAD_REQUEST);
        }

        if ('paid' === $status) {
            $order->setPaymentState(OrderPaymentStates::STATE_PAID);
            $payment->setState(OrderPaymentStates::STATE_PAID);
        } elseif (in_array($status, ['failed', 'expired', 'canceled'], true)) {
            $order->setPaymentState(OrderPaymentStates::STATE_CANCELLED);
            $payment->setState(OrderPaymentStates::STATE_CANCELLED);
        } else {
            return new JsonResponse(['code' => 202, 'message' => 'Event ignored'], Response::HTTP_ACCEPTED);
        }

        $em = $this->container->get('doctrine.orm.entity_manager');
        $em->persist($order);
        $em->flush();

        return new JsonResponse(['code' => 200, 'message' => 'Order payment state updated']);
    }

    private function isValidSignature(Request $request, string $webhookSecret): bool
    {
        $provided = $request->headers->get('signature')
            ?? $request->headers->get('x-chargily-signature')
            ?? '';

        if ('' === $provided) {
            return false;
        }

        $calculated = hash_hmac('sha256', $request->getContent(), $webhookSecret);

        return hash_equals($calculated, $provided);
    }
}
