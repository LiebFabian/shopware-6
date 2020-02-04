<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\PaymentHandler;

use HeidelPayment6\Components\PaymentHandler\Traits\CanCharge;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HeidelDirectDebitGuaranteedPaymentHandler extends AbstractHeidelpayHandler
{
    use CanCharge;

    /**
     * {@inheritdoc}
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        parent::pay($transaction, $dataBag, $salesChannelContext);

        $birthday = $dataBag->get('heidelpayBirthday');

        if ($dataBag->get('acceptSepaMandate') !== 'on') {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'SEPA direct debit mandate has not been accepted by the customer.');
        }

        try {
            $this->heidelpayCustomer->setBirthDate($birthday);
            $this->heidelpayCustomer = $this->heidelpayClient->createOrUpdateCustomer($this->heidelpayCustomer);

            $returnUrl = $this->charge($transaction->getReturnUrl());

            return new RedirectResponse($returnUrl);
        } catch (HeidelpayApiException $apiException) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $apiException->getClientMessage());
        }
    }
}