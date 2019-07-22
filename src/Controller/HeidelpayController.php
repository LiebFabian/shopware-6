<?php

declare(strict_types=1);

namespace HeidelPayment\Controller;

use HeidelPayment\Components\Client\ClientFactory;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HeidelpayController extends StorefrontController
{
    /** @var SessionInterface */
    private $session;

    /** @var ClientFactory */
    private $clientFactory;

    public function __construct(
        SessionInterface $session,
        ClientFactory $clientFactory
    ) {
        $this->session       = $session;
        $this->clientFactory = $clientFactory;
    }

    /**
     * @deprecated Should be removed as soon as the shopware finalize URL is shorter so that Heidelpay can handle it!
     *
     * @Route("/heidelpay/finalizePayment", name="heidelpay_finalize_payment", methods={"GET"})
     */
    public function finalizePayment(): RedirectResponse
    {
        $metadataId      = $this->session->get('heidelpayMetadataId');
        $heidelpayClient = $this->clientFactory->createClient();

        $metadata          = $heidelpayClient->fetchMetadata($metadataId);
        $actualRedirectUrl = $metadata->getMetadata('returnUrl');

        return $this->redirect($actualRedirectUrl);
    }
}
