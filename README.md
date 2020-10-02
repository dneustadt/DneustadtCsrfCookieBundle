# CSRF Cookie Bundle

This [Symfony](http://symfony.com) bundle provides [Cross Site Request Forgery](http://en.wikipedia.org/wiki/Cross-site_request_forgery)
(CSRF or XSRF) protection for client-side applications requesting endpoints provided by Symfony through XHR.

Heavily influenced and inspired by [DunglasAngularCsrfBundle](https://github.com/dunglas/DunglasAngularCsrfBundle)

## Requirements

* Symfony >= 5.x

## Working method

To store the CSRF token client-side a cookie containing the token can be set by one or more predetermined routes.
The bundle is pre-configured in a way that modern client-side http clients such as [Axios](https://github.com/axios/axios)
will automatically pick up said cookie. On subsequent requests to Symfony the CSRF token can then be added to the
HTTP header to be validated server-side. Again, some clients may already do so automatically e.g. Axios.

## Installation

Use [Composer](http://getcomposer.org/) to install this bundle:

```
composer require dneustadt/csrf-cookie-bundle
```

## General Configuration
```yaml
# config/packages/dneustadt_csrf_cookie.yaml
dneustadt_csrf_cookie:
    # Generally enable/disable the CSRF protection
    enable: true
    # ID used to generate token
    id: csrf
    # Name of the cookie the token is stored in
    name: XSRF-TOKEN
    # Cookie expiration
    expire: 0
    # Cookie path
    path: /
    # Cookie domain
    domain: null
    # Cookie secure
    secure: false
    # Name of the HTTP header the token is expected to be stored in
    header: X-XSRF-TOKEN
    # Cookie same site policy
    sameSite: lax
```

## Routes Configurations

Routes can be set up to either provide (`create`) a token, be secured by (`require`) a token or both.

Since the defaults of a single route or a route collection are used to configure the behaviour it is possible
to do so either by means of configuration files or annotations.

```yaml
# config/routes.yaml
api_controllers:
    resource: ../src/Controller/Api
    type: annotation
    defaults:
        csrf:
            # bool or array of allowed methods
            create: true
            # bool or array of allowed methods
            require:
                - 'POST'
                - 'PUT'
                - 'PATCH'
                - 'DELETE'
            # array of route names to be excluded from create/require in this collection
            exclude:
                - 'app_api_blog_index'
            # additional condition using ExpressionLanguage syntax
            condition: 'request.isXmlHttpRequest()'
```

For more information on conditions see [ExpressionLanguage](https://symfony.com/doc/current/components/expression_language.html)

As annotation:

```php
// src/Controller/Api/ExampleController.php
namespace App\Controller\Api;

// ...

class ExampleController extends AbstractController
{
    /**
     * @Route("/api/index", methods={"GET","HEAD"}, defaults={"csrf": {"create": true}})
     */
    public function index()
    {
        // ...
    }
}
```

## Symfony Form Component

Built-in CSRF Protection of forms will be automatically disabled for routes that are configured to be secured by means
of the token stored in the HTTP header, provided said token can be successfully validated.
