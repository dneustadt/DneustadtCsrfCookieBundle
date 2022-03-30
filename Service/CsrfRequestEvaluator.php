<?php

declare(strict_types=1);

namespace Dneustadt\CsrfCookieBundle\Service;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfRequestEvaluator
{
    public const REQUIRE = 'require';
    public const CREATE = 'create';

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $tokenManager;

    /**
     * @var bool
     */
    protected $cookieEnable;

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
        bool $cookieEnable,
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
        $this->cookieEnable = $cookieEnable;
        $this->cookieId = $cookieId;
        $this->cookieName = $cookieName;
        $this->cookieExpire = $cookieExpire;
        $this->cookiePath = $cookiePath;
        $this->cookieDomain = $cookieDomain;
        $this->cookieSecure = $cookieSecure;
        $this->cookieHeader = $cookieHeader;
        $this->cookieSameSite = $cookieSameSite;
    }

    public function getHeader(Request $request, ?Response $response): void
    {
        if ($this->isDisabled(self::REQUIRE, $request, $response)) {
            return;
        }

        $this->isTokenValid($request);
    }

    public function isTokenValid(Request $request, bool $throwException = true): bool
    {
        $token = $request->headers->has($this->cookieHeader) ?
            $request->headers->get($this->cookieHeader) :
            $request->cookies->get($this->cookieHeader);

        if (empty($token)) {
            if ($throwException === false) {
                return false;
            }

            throw new AccessDeniedHttpException('The CSRF token is invalid. Please try to resubmit the form.');
        }

        $token = new CsrfToken($this->cookieId, $token);

        if (!$this->tokenManager->isTokenValid($token)) {
            if ($throwException === false) {
                return false;
            }

            throw new AccessDeniedHttpException('The CSRF token is invalid. Please try to resubmit the form.');
        }

        return true;
    }

    public function setCookie(Request $request, Response $response): void
    {
        if ($this->isDisabled(self::CREATE, $request, $response)) {
            return;
        }

        $response->headers->setCookie(
            new Cookie(
                $this->cookieName,
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

    public function isDisabled(string $procedure, Request $request, ?Response $response): bool
    {
        $attributes = $request->attributes;
        $csrf = $attributes->get('csrf');

        return $this->cookieEnable === false
            || !is_array($csrf)
            || empty($csrf[$procedure])
            || (!empty($csrf['exclude']) && is_array($csrf['exclude']) && in_array($attributes->get('_route'), $csrf['exclude'], true))
            || (!empty($csrf['condition']) && $this->evaluateCondition($csrf['condition'], $request, $response) === false)
            || (is_array($csrf[$procedure]) && !in_array($request->getMethod(), $csrf[$procedure], true))
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
