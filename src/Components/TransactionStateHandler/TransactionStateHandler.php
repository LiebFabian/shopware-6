<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\TransactionStateHandler;

use HeidelPayment6\Components\DependencyInjection\Factory\PaymentTransitionMapperFactory;
use HeidelPayment6\Components\PaymentTransitionMapper\Exception\NoTransitionMapperFoundException;
use HeidelPayment6\Components\PaymentTransitionMapper\Exception\TransitionMapperException;
use heidelpayPHP\Resources\Payment;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransactionStateHandler implements TransactionStateHandlerInterface
{
    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    /** @var PaymentTransitionMapperFactory */
    private $transitionMapperFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        StateMachineRegistry $stateMachineRegistry,
        PaymentTransitionMapperFactory $transitionMapperFactory,
        LoggerInterface $logger
    ) {
        $this->stateMachineRegistry    = $stateMachineRegistry;
        $this->transitionMapperFactory = $transitionMapperFactory;
        $this->logger                  = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function transformTransactionState(
        string $transactionId,
        Payment $payment,
        Context $context
    ): void {
        if (null === $payment->getPaymentType()) {
            return;
        }

        $transition = $this->getTargetTransition($payment);

        if (empty($transition)) {
            $this->executeTransition($transactionId, StateMachineTransitionActions::ACTION_FAIL, $context);

            throw new RuntimeException('Invalid transition status');
        }

        $this->executeTransition($transactionId, $transition, $context);
    }

    protected function getTargetTransition(Payment $payment): string
    {
        try {
            $transitionMapper = $this->transitionMapperFactory->getTransitionMapper($payment->getPaymentType());
            $transition       = $transitionMapper->getTargetPaymentStatus($payment);
        } catch (NoTransitionMapperFoundException | TransitionMapperException $exception) {
            $this->logger->error($exception->getMessage(), [
                'code'  => $exception->getCode(),
                'file'  => $exception->getFile(),
                'line'  => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return $transition ?? '';
    }

    protected function executeTransition(string $transactionId, string $transition, Context $context): void
    {
        try {
            $this->stateMachineRegistry->transition(
                new Transition(
                    OrderTransactionDefinition::ENTITY_NAME,
                    $transactionId,
                    $transition,
                    'stateId'
                ),
                $context
            );

            // If the previous state is "paid_partially", "paid" is currently not allowed as direct transition
            if ($transition === StateMachineTransitionActions::ACTION_DO_PAY) {
                $this->stateMachineRegistry->transition(
                    new Transition(
                        OrderTransactionDefinition::ENTITY_NAME,
                        $transactionId,
                        StateMachineTransitionActions::ACTION_PAID,
                        'stateId'
                    ),
                    $context
                );
            }
        } catch (IllegalTransitionException $exception) {
            // false positive handling (state to state) like open -> open, paid -> paid, etc.
        }
    }
}
