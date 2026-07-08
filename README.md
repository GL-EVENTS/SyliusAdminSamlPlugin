<h1 align="center">GL events SyliusAdminSamlPlugin</h1>


## Features

This plugin allow your admin users to sign in with SAML providers (Google, Azure, Okta, etc.)

<p align="center">
    <img src="docs/login.png" alt="Form admin login" />
</p>

## Installation

1. Add the bundle to your `composer.json` file:
 ```bash
    composer require gl-events/sylius-admin-saml-plugin
    composer require onelogin/php-saml
  ```

2. Add the plugin class to your `config/bundles.php` file:

```php
    return [
        ...
        GlEvents\SyliusAdminSamlPlugin\GlEventsSyliusAdminSamlPlugin::class => ['all' => true],
    ];
```

3. Import the plugin defaults (registers the SSO button hook and the login-page Twig globals):

```yaml
# config/packages/gl_events_sylius_admin_saml.yaml

imports:
    - { resource: "@GlEventsSyliusAdminSamlPlugin/config/config.yaml" }
```

4. Configure the plugin. Everything is set through the bundle configuration, no dedicated env vars are required. Generate the SP private key with `openssl genpkey -algorithm RSA -out private.key`:

```yaml
# config/packages/gl_events_sylius_admin_saml.yaml

gl_events_sylius_admin_saml:
    idp:
        entity_id: 'https://idp.example.com/metadata'
        sso_url: 'https://idp.example.com/sso'
        slo_url: 'https://idp.example.com/slo'   # optional
        certificate: 'MIID...'
    sp:
        private_key: |
            -----BEGIN PRIVATE KEY-----
            ...
            -----END PRIVATE KEY-----
    identifier_key: 'email'   # SAML attribute holding the admin user email
    proxy_vars: false         # true when behind an SSL-terminating reverse proxy
    admin_login: true         # show the traditional Sylius admin login form
    sso_login: true           # show the SSO login button
```

   Every setting is optional and defaults to an empty string (`proxy_vars` to `false`, `admin_login`/`sso_login` to `true`); a missing SAML setting simply makes the SSO login fail at runtime (flash + redirect back to the login page), it never breaks the app boot. Any value may still reference an env var if you prefer, e.g. `certificate: '%env(SAML_IDP_CERTIFICATE)%'`.

5. Wrap the traditional admin login form so it can be toggled with `admin_login`.

   The plugin does not override the core Sylius admin login hooks (a plugin must never override the host app's defaults). Gate the whole form with a single hook override in `config/packages/sylius_twig_hooks.yaml`:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.security.login.page.content':
            form:
                template: '@GlEventsSyliusAdminSamlPlugin/Security/login/page/content/form/_traditional.html.twig'
                context:
                    wrapped: '@SyliusAdmin/security/login/page/content/form.html.twig'
```

   This wraps the entire `<form>` (fields + CSRF) in the `admin_login` condition, so in SSO-only mode the form disappears cleanly. The SSO button hook is provided by the plugin on the sibling `sylius_admin.security.login.page.content` hook, so it stays visible regardless.

6. Add in your `config/security.yaml` file:

```yaml
        providers:
            saml_provider:
              id: gl_events.saml_plugin.provider.saml_user
        firewalls:
              saml:
                    pattern: ^/saml
                    stateless: true
                    custom_authenticator: gl_events.saml_plugin.security.saml_authenticator
              main:
                    lazy: true
                    provider: saml_provider
        access_control:
              - { path: "%sylius.security.admin_regex%/saml", role: ROLE_SUPER_ADMIN }
              - { path: "%sylius.security.admin_regex%/login/saml", role: PUBLIC_ACCESS }
              - { path: "%sylius.security.admin_regex%/login/saml/logout", role: PUBLIC_ACCESS }
              - { path: "%sylius.security.admin_regex%/login/saml/acs", role: PUBLIC_ACCESS }
              - { path: "%sylius.security.admin_regex%/login/saml/sls", role: PUBLIC_ACCESS }
              - { path: "%sylius.security.admin_regex%/login/saml/metadata", role: PUBLIC_ACCESS }

```
7. Add in your `config/routes.yaml` file:

```yaml
   glevents_sylius_admin_saml_plugin:
        resource: "@GlEventsSyliusAdminSamlPlugin/config/routing.yaml"
```

8. If your application runs behind a reverse proxy (load balancer, Kubernetes ingress, etc.) that terminates SSL, set `proxy_vars: true` in your `gl_events_sylius_admin_saml` config:

```yaml
gl_events_sylius_admin_saml:
    proxy_vars: true
```

This tells the `onelogin/php-saml` library to read `X-Forwarded-Proto`, `X-Forwarded-Host` and `X-Forwarded-Port` headers when building the current URL for SAML response validation. Without this, the library detects `http://` instead of `https://` and rejects the SAML response with an error like _"The response was received at http://... instead of https://..."_.

Also verify your Symfony `trusted_proxies` and `trusted_headers` settings so that `$request->getScheme()` also returns the correct scheme, see: https://symfony.com/doc/current/deployment/proxies.html#but-what-if-the-ip-of-my-reverse-proxy-changes-constantly


9. You are now ready to go  ! 🚀

## Credits

Developed by [GL Events](https://gl-events.com/).
