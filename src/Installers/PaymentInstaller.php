<?php

declare(strict_types=1);

namespace HeidelPayment6\Installers;

use HeidelPayment6\Components\PaymentHandler\HeidelAlipayPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelCreditCardPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelDirectDebitGuaranteedPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelDirectDebitPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelEpsPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelFlexipayPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelGiropayPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelHirePurchasePaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelIdealPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelInvoiceFactoringPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelInvoiceGuaranteedPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelInvoicePaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelPayPalPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelPrePaymentPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelPrzelewyHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelSofortPaymentHandler;
use HeidelPayment6\Components\PaymentHandler\HeidelWeChatPaymentHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class PaymentInstaller implements InstallerInterface
{
    public const PAYMENT_ID_CREDIT_CARD             = '4673044aff79424a938d42e9847693c3';
    public const PAYMENT_ID_SOFORT                  = '95aa098aac8f11e9a2a32a2ae2dbcce4';
    public const PAYMENT_ID_INVOICE                 = '08fb8d9a72ab4ca62b811e74f2eca79f';
    public const PAYMENT_ID_INVOICE_GUARANTEED      = '78f3cfa6ab2d9168759724e7cde1eab2';
    public const PAYMENT_ID_INVOICE_FACTORING       = '6cc3b56ce9b0f80bd44039c047282a41';
    public const PAYMENT_ID_EPS                     = '17830aa7e6a00b99eab27f0e45ac5e0d';
    public const PAYMENT_ID_PAYPAL                  = '409fe641d6d62a4416edd6307d758791';
    public const PAYMENT_ID_FLEXIPAY                = '4ebb99451f36ba01f13d5871a30bce2c';
    public const PAYMENT_ID_GIROPAY                 = 'd4b90a17af62c1bb2f6c3b1fed339425';
    public const PAYMENT_ID_IDEAL                   = '614ad722a03ee96baa2446793143215b';
    public const PAYMENT_ID_DIRECT_DEBIT            = '713c7a332b432dcd4092701eda522a7e';
    public const PAYMENT_ID_DIRECT_DEBIT_GUARANTEED = '5123af5ce94a4a286641973e8de7eb60';
    public const PAYMENT_ID_PRZELEWY24              = 'cd6f59d572e6c90dff77a48ce16b44db';
    public const PAYMENT_ID_PRE_PAYMENT             = '085b64d0028a8bd447294e03c4eb411a';
    public const PAYMENT_ID_ALIPAY                  = 'bc4c2cbfb5fda0bf549e4807440d0a54';
    public const PAYMENT_ID_WE_CHAT                 = 'fd96d03535a46d197f5adac17c9f8bac';
    public const PAYMENT_ID_HIRE_PURCHASE           = '4b9f8d08b46a83839fd0eb14fe00efe6';

    public const PAYMENT_METHODS = [
        [
            'id'                => self::PAYMENT_ID_CREDIT_CARD,
            'handlerIdentifier' => HeidelCreditCardPaymentHandler::class,
            'name'              => 'Credit card (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Kreditkarte (heidelpay)',
                    'description' => 'Kreditkartenzahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Credit card (heidelpay)',
                    'description' => 'Credit card payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_INVOICE,
            'handlerIdentifier' => HeidelInvoicePaymentHandler::class,
            'name'              => 'Invoice (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Rechnung (heidelpay)',
                    'description' => 'Rechnungskauf mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Invoice (heidelpay)',
                    'description' => 'Invoice payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_SOFORT,
            'handlerIdentifier' => HeidelSofortPaymentHandler::class,
            'name'              => 'Sofort (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Sofort (heidelpay)',
                    'description' => 'Sofort mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Sofort (heidelpay)',
                    'description' => 'Sofort with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_INVOICE_GUARANTEED,
            'handlerIdentifier' => HeidelInvoiceGuaranteedPaymentHandler::class,
            'name'              => 'FlexiPay® Invoice guaranteed (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'FlexiPay® Rechnung (heidelpay)',
                    'description' => 'FlexiPay® Rechnungskauf mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Invoice guaranteed (heidelpay)',
                    'description' => 'Invoice guaranteed payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_INVOICE_FACTORING,
            'handlerIdentifier' => HeidelInvoiceFactoringPaymentHandler::class,
            'name'              => 'FlexiPay® Invoice factoring (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'FlexiPay® Rechnung mit factoring (heidelpay)',
                    'description' => 'FlexiPay® Rechnungskauf factoring mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'FlexiPay®Invoice factoring (heidelpay)',
                    'description' => 'FlexiPay® Invoice factoring payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_EPS,
            'handlerIdentifier' => HeidelEpsPaymentHandler::class,
            'name'              => 'EPS (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'EPS (heidelpay)',
                    'description' => 'EPS Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'EPS (heidelpay)',
                    'description' => 'EPS payments with Heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_FLEXIPAY,
            'handlerIdentifier' => HeidelFlexipayPaymentHandler::class,
            'name'              => 'FlexiPay® Direct(heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'FlexiPay® Direct(heidelpay)',
                    'description' => 'FlexiPay® Direct Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'FlexiPay® Direct (heidelpay)',
                    'description' => 'FlexiPay® Direct payments with Heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_PAYPAL,
            'handlerIdentifier' => HeidelPayPalPaymentHandler::class,
            'name'              => 'PayPal (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'PayPal (heidelpay)',
                    'description' => 'PayPal Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'PayPal (heidelpay)',
                    'description' => 'PayPal payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_IDEAL,
            'handlerIdentifier' => HeidelIdealPaymentHandler::class,
            'name'              => 'iDEAL (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'iDEAL (heidelpay)',
                    'description' => 'iDEAL Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'iDEAL (heidelpay)',
                    'description' => 'iDEAL payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_DIRECT_DEBIT,
            'handlerIdentifier' => HeidelDirectDebitPaymentHandler::class,
            'name'              => 'SEPA direct debit (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'SEPA Lastschrift (heidelpay)',
                    'description' => 'SEPA Lastschrift Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'SEPA direct debit (heidelpay)',
                    'description' => 'SEPA direct debit payments with Heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_DIRECT_DEBIT_GUARANTEED,
            'handlerIdentifier' => HeidelDirectDebitGuaranteedPaymentHandler::class,
            'name'              => 'SEPA direct debit guaranteed (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'SEPA Lastschrift gesichert (heidelpay)',
                    'description' => 'Gesicherte SEPA Lastschrift Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'SEPA direct debit guaranteed (heidelpay)',
                    'description' => 'Guaranteed SEPA direct debit payments with Heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_GIROPAY,
            'handlerIdentifier' => HeidelGiropayPaymentHandler::class,
            'name'              => 'Giropay (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Giropay (heidelpay)',
                    'description' => 'Giropay Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Giropay (heidelpay)',
                    'description' => 'Giropay payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_PRE_PAYMENT,
            'handlerIdentifier' => HeidelPrePaymentPaymentHandler::class,
            'name'              => 'Prepayment (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Vorkasse (heidelpay)',
                    'description' => 'Zahlung auf Vorkasse mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Prepayment (heidelpay)',
                    'description' => 'Prepayment with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_PRZELEWY24,
            'handlerIdentifier' => HeidelPrzelewyHandler::class,
            'name'              => 'Przelewy24 (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Przelewy24 (heidelpay)',
                    'description' => 'Przelewy24 Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Przelewy24 (heidelpay)',
                    'description' => 'Przelewy24 payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_WE_CHAT,
            'handlerIdentifier' => HeidelWeChatPaymentHandler::class,
            'name'              => 'WeChat (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'WeChat (heidelpay)',
                    'description' => 'WeChat Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'WeChat (heidelpay)',
                    'description' => 'WeChat payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_ALIPAY,
            'handlerIdentifier' => HeidelAlipayPaymentHandler::class,
            'name'              => 'Alipay (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Alipay (heidelpay)',
                    'description' => 'Alipay Zahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Alipay (heidelpay)',
                    'description' => 'Alipay payments with heidelpay',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_HIRE_PURCHASE,
            'handlerIdentifier' => HeidelHirePurchasePaymentHandler::class,
            'name'              => 'FlexiPay® Rate (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'FlexiPay® Rate (heidelpay)',
                    'description' => 'FlexiPay® Ratenzahlungen mit heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'FlexiPay® Instalment (heidelpay)',
                    'description' => 'FlexiPay® Instalment payments with heidelpay',
                ],
            ],
        ],
    ];

    /** @var EntityRepositoryInterface */
    private $paymentMethodRepository;

    public function __construct(EntityRepositoryInterface $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function install(InstallContext $context): void
    {
        $this->paymentMethodRepository->upsert(self::PAYMENT_METHODS, $context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        $this->paymentMethodRepository->upsert(self::PAYMENT_METHODS, $context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->setAllPaymentMethodsActive(false, $context);
    }

    public function activate(ActivateContext $context): void
    {
        $this->setAllPaymentMethodsActive(true, $context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->setAllPaymentMethodsActive(false, $context);
    }

    public static function getPaymentIds(): array
    {
        return array_column(self::PAYMENT_METHODS, 'id');
    }

    private function setAllPaymentMethodsActive(bool $active, InstallContext $context): void
    {
        $upsertPayload = [];
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $paymentMethodCriteria = new Criteria([$paymentMethod['id']]);
            $hasPaymentMethod      = $this->paymentMethodRepository->searchIds($paymentMethodCriteria, $context->getContext())->getTotal() > 0;

            if (!$hasPaymentMethod) {
                continue;
            }

            $upsertPayload[] = [
                'id'     => $paymentMethod['id'],
                'active' => $active,
            ];
        }

        $this->paymentMethodRepository->upsert($upsertPayload, $context->getContext());
    }
}
