<?php

declare(strict_types=1);

namespace Dneustadt\CsrfCookieBundle\Form\Extension;

use Dneustadt\CsrfCookieBundle\Service\CsrfRequestEvaluator;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisableCsrfExtension extends AbstractTypeExtension
{
    /**
     * @var CsrfRequestEvaluator
     */
    protected $csrfRequest;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    public function __construct(
        CsrfRequestEvaluator $csrfRequest,
        RequestStack $requestStack
    ) {
        $this->csrfRequest = $csrfRequest;
        $this->requestStack = $requestStack;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        if ($this->csrfRequest->isDisabled(CsrfRequestEvaluator::REQUIRE, $request, null)) {
            return;
        }

        if ($this->csrfRequest->isTokenValid($request)) {
            $resolver->setDefaults([
                'csrf_protection' => false,
            ]);
        }
    }

    public function getExtendedType()
    {
        return FormType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
