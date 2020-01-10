<?php

declare(strict_types=1);

namespace HeidelPayment6\Commands;

use HeidelPayment6\Components\ClientFactory\ClientFactoryInterface;
use HeidelPayment6\Components\ConfigReader\ConfigReaderInterface;
use HeidelPayment6\EventListeners\StateMachine\TransitionEventListener;
use HeidelPayment6\Installers\CustomFieldInstaller;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use RuntimeException;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendShippingNotificationCommand extends Command
{
    private const EXIT_CODE_SUCCESS       = 0;
    private const EXIT_CODE_API_ERROR     = 1;
    private const EXIT_CODE_UNKNOWN_ERROR = 2;
    private const EXIT_CODE_NO_ORDERS     = 3;
    private const EXIT_CODE_CONFIGURATION = 4;

    private const API_ERROR_ALREADY_INSURED = 'COR.700.400.800';

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var Context */
    private $context;

    public function __construct(ConfigReaderInterface $configReader, ClientFactoryInterface $clientFactory, EntityRepositoryInterface $transactionRepository)
    {
        $this->configReader          = $configReader;
        $this->clientFactory         = $clientFactory;
        $this->transactionRepository = $transactionRepository;
        $this->context               = Context::createDefaultContext();

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('heidelpay:send:shipping')
            ->setDescription('Send all shipping notifications for the necessary orders to heidelpay.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config          = $this->configReader->read();
        $configuredState = $config->get('statusForAutomaticShippingNotification');

        if (empty($configuredState)) {
            $output->writeln('<error>Execution aborted: Please configure a value for [Order status for shipping notification] in your plugin settings.</error>');

            return self::EXIT_CODE_CONFIGURATION;
        }

        $transactions = $this->getMatchingTransactions($configuredState);
        $max          = $transactions->count();

        if ($max === 0) {
            $output->writeln('<info>No orders found for automatic shipping notification.</info>');

            return self::EXIT_CODE_NO_ORDERS;
        }

        $output->writeln(sprintf('<info>Found %s possible order(s) for automatic shipping notification</info>', $max));
        $current = 0;

        /** @var OrderTransactionEntity $transaction */
        foreach ($transactions as $transaction) {
            ++$current;

            $order = $transaction->getOrder();

            $output->write(sprintf('(%s/%s) Order %s', $current, $max, $order->getOrderNumber()));

            $entityFilter = new DocumentTypeEntity();
            $entityFilter->setTechnicalName('invoice');
            $documentId = $this->getInvoiceDocumentId($order->getDocuments());

            try {
                $client = $this->clientFactory->createClient($order->getSalesChannelId());
                $client->ship($transaction->getId(), $documentId);

                $this->setCustomFields($transaction);

                $output->writeln("\t<info>OK</info>");
            } catch (HeidelpayApiException $apiException) {
                $output->writeln(sprintf("\t<error>%s</error>", $apiException->getMerchantMessage()));

                //Already insured but flag in DB missing!
                if ($apiException->getCode() === self::API_ERROR_ALREADY_INSURED) {
                    $this->setCustomFields($transaction);

                    return self::EXIT_CODE_SUCCESS;
                }

                return self::EXIT_CODE_API_ERROR;
            } catch (RuntimeException $exception) {
                $output->writeln(sprintf("\t<error>%s</error>", $exception->getMessage()));

                return self::EXIT_CODE_UNKNOWN_ERROR;
            }
        }

        return self::EXIT_CODE_SUCCESS;
    }

    protected function setCustomFields(
        OrderTransactionEntity $transaction
    ): void {
        $customFields = $transaction->getCustomFields() ?? [];
        $customFields = array_merge($customFields, [
            CustomFieldInstaller::HEIDELPAY_IS_SHIPPED => true,
        ]);

        $update = [
            'id'           => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $this->transactionRepository->update([$update], $this->context);
    }

    private function getMatchingTransactions(string $stateId): EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter(sprintf('customFields.%s', CustomFieldInstaller::HEIDELPAY_IS_SHIPPED), false),
            new EqualsAnyFilter('paymentMethodId', TransitionEventListener::HANDLED_PAYMENT_METHODS),
            new EqualsFilter('order.deliveries.stateId', $stateId),
            new EqualsFilter('order.documents.documentType.technicalName', 'invoice')
        )->addAssociations([
            'order',
            'order.deliveries',
            'order.documents',
            'order.documents.documentType',
        ]);

        return $this->transactionRepository->search($criteria, $this->context)->getEntities();
    }

    private function getInvoiceDocumentId(DocumentCollection $documents): ?string
    {
        $invoice = $documents->filter(static function (DocumentEntity $entity) {
            if ($entity->getDocumentType()->getTechnicalName() === 'invoice') {
                return $entity;
            }

            return null;
        })->first();

        return $invoice->getConfig()['documentNumber'];
    }
}
