<?php

declare(strict_types=1);

namespace Dneustadt\CsrfCookieBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class KernelEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var CsrfTokenManagerInterface
     */
    protected $tokenManager;

    /**
     * @var string
     */
    protected $cookieId;

    /**
     * @var string
     */
    protected $cookieName;

    /**
     * @var int
     */
    protected $cookieExpire;

    /**
     * @var string
     */
    protected $cookiePath;

    /**
     * @var ?string
     */
    protected $cookieDomain;

    /**
     * @var bool
     */
    protected $cookieSecure;

    /**
     * @var string
     */
    protected $cookieHeader;

    public function __construct(
        CsrfTokenManagerInterface $tokenManager,
        string $cookieId,
        string $cookieName,
        int $cookieExpire,
        string $cookiePath,
        ?string $cookieDomain,
        bool $cookieSecure,
        string $cookieHeader
    ) {
        $this->tokenManager = $tokenManager;
        $this->cookieId = $cookieId;
        $this->cookieName = $cookieName;
        $this->cookieExpire = $cookieExpire;
        $this->cookiePath = $cookiePath;
        $this->cookieDomain = $cookieDomain;
        $this->cookieSecure = $cookieSecure;
        $this->cookieHeader = $cookieHeader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $attributes = $event->getRequest()->attributes;
        $csrf = $attributes->get('csrf');

        if (
            empty($csrf)
            || empty($csrf['create'])
            || (!empty($csrf['exclude']) && is_array($csrf['exclude']) && in_array($attributes->get('_route'), $csrf['exclude'], true))
        ) {
            return;
        }

        if (
            $csrf['require'] === true
            || (is_array($csrf['require']) && in_array($event->getRequest()->getMethod(), $csrf['require'], true))
        ) {
            $token = $event->getRequest()->headers->get($this->cookieHeader);

            if (empty($token)) {
                throw new AccessDeniedHttpException('The CSRF token is invalid. Please try to resubmit the form.');
            }

            $token = new CsrfToken($this->cookieId, $token);

            if (!$this->tokenManager->isTokenValid($token)) {
                throw new AccessDeniedHttpException('The CSRF token is invalid. Please try to resubmit the form.');
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $attributes = $event->getRequest()->attributes;
        $csrf = $attributes->get('csrf');

        if (
            empty($csrf)
            || empty($csrf['create'])
            || (!empty($csrf['exclude']) && is_array($csrf['exclude']) && in_array($attributes->get('_route'), $csrf['exclude'], true))
        ) {
            return;
        }

        if (
            $csrf['create'] === true
            || (is_array($csrf['create']) && in_array($event->getRequest()->getMethod(), $csrf['create'], true))
        ) {
            $event->getResponse()->headers->setCookie(
                new Cookie(
                    'XSRF-TOKEN',
                    $this->tokenManager
                        ->refreshToken($this->cookieId)
                        ->getValue(),
                    $this->cookieExpire === 0 ? $this->cookieExpire : time() + $this->cookieExpire,
                    $this->cookiePath,
                    $this->cookieDomain,
                    $this->cookieSecure,
                    false,
                    false,
                    Cookie::SAMESITE_LAX
                )
            );
        }
    }
}
