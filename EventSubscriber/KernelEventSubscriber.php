<?php

declare(strict_types=1);

namespace Dneustadt\CsrfCookieBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    /**
     * @var string
     */
    protected $cookieSameSite;

    public function __construct(
        CsrfTokenManagerInterface $tokenManager,
        string $cookieId,
        string $cookieName,
        int $cookieExpire,
        string $cookiePath,
        ?string $cookieDomain,
        bool $cookieSecure,
        string $cookieHeader,
        string $cookieSameSite
    ) {
        $this->tokenManager = $tokenManager;
        $this->cookieId = $cookieId;
        $this->cookieName = $cookieName;
        $this->cookieExpire = $cookieExpire;
        $this->cookiePath = $cookiePath;
        $this->cookieDomain = $cookieDomain;
        $this->cookieSecure = $cookieSecure;
        $this->cookieHeader = $cookieHeader;
        $this->cookieSameSite = $cookieSameSite;
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
        $csrf = $event->getRequest()->attributes->get('csrf');

        if (
            !is_array($csrf)
            || $this->isCsrfDisabled($csrf, 'require', $event->getRequest(), $event->getResponse())
        ) {
            return;
        }

        $token = $event->getRequest()->headers->get($this->cookieHeader);

        if (empty($token)) {
            throw new AccessDeniedHttpException('The CSRF token is invalid. Please try to resubmit the form.');
        }

        $token = new CsrfToken($this->cookieId, $token);

        if (!$this->tokenManager->isTokenValid($token)) {
            throw new AccessDeniedHttpException('The CSRF token is invalid. Please try to resubmit the form.');
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $csrf = $event->getRequest()->attributes->get('csrf');

        if (
            !is_array($csrf)
            || $this->isCsrfDisabled($csrf, 'create', $event->getRequest(), $event->getResponse())
        ) {
            return;
        }

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
                $this->cookieSameSite
            )
        );
    }

    private function isCsrfDisabled(array $csrf, string $procedure, Request $request, ?Response $response): bool
    {
        $attributes = $request->attributes;

        return empty($csrf[$procedure])
            || (!empty($csrf['exclude']) && is_array($csrf['exclude']) && in_array($attributes->get('_route'), $csrf['exclude'], true))
            || (!empty($csrf['condition']) && $this->evaluateCondition($csrf['condition'], $request, $response) === false)
            || (is_array($csrf[$procedure]) && !in_array($request->getMethod(), $csrf['require'], true))
        ;
    }

    private function evaluateCondition(string $condition, Request $request, ?Response $response): bool
    {
        $expressionLanguage = new ExpressionLanguage();

        return (bool) $expressionLanguage->evaluate(
            $condition,
            [
                'request' => $request,
                'response' => $response,
            ]
        );
    }
}
