<?php

declare(strict_types=1);

namespace Dneustadt\CsrfCookieBundle\EventSubscriber;

use Dneustadt\CsrfCookieBundle\Service\CsrfRequestEvaluator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var CsrfRequestEvaluator
     */
    protected $csrfRequest;

    public function __construct(CsrfRequestEvaluator $csrfRequest)
    {
        $this->csrfRequest = $csrfRequest;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 12],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->csrfRequest->getHeader($event->getRequest(), $event->getResponse());
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->csrfRequest->setCookie($event->getRequest(), $event->getResponse());
    }
}
