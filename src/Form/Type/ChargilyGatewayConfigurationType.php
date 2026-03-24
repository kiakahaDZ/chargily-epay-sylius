<?php

declare(strict_types=1);

namespace Chargily\EpayPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ChargilyGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('secret_key', TextType::class, [
                'required' => true,
                'label' => 'Secret key',
            ])
            ->add('webhook_secret', TextType::class, [
                'required' => false,
                'label' => 'Webhook secret (optional)',
            ])
            ->add('api_base_url', TextType::class, [
                'required' => true,
                'label' => 'API base URL',
            ])
            ->add('description', TextType::class, [
                'required' => true,
                'label' => 'Checkout description',
            ])
            ->add('success_url', TextType::class, [
                'required' => true,
                'label' => 'Success URL',
            ])
            ->add('failure_url', TextType::class, [
                'required' => true,
                'label' => 'Failure URL',
            ])
            ->add('locale', ChoiceType::class, [
                'choices' => [
                    'Arabic' => 'ar',
                    'English' => 'en',
                    'French' => 'fr',
                ],
                'label' => 'Checkout locale',
            ])
            ->add('payment_method', ChoiceType::class, [
                'choices' => [
                    'EDAHABIA' => 'edahabia',
                    'CIB' => 'cib',
                    'Chargily App' => 'chargily_app',
                ],
                'label' => 'Default payment method',
            ]);
    }
}
