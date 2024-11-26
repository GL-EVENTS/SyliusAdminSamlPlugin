<h1 align="center">GL events SyliusAdminSamlPlugin</h1>


## Features

This plugin allow your admin users to subscribe and sign in with SAML providers (Google, Azure, Okta, etc.)

![Shop account address book](docs/login.png "Form admin login")


## Installation

1. Add the bundle to your `composer.json` file:
 ```bash
    composer require glevents/sylius-admin-saml-plugin
  ```
2. Write your Identity Provider informations in your `.env` file:
 ```bash
    SAML_IDP_ENTITY_ID=
    SAML_IDP_SSO_URL=
    SAML_IDP_SLO_URL=
    SAML_IDP_CERTIFICATE=
    SAML_IDENTIFIER_KEY=
```
3. Add your SP private key in your `.env` file (you can generate one at your project root with `openssl genpkey -algorithm RSA -out private.key`):
 ```bash
    SAML_SP_PRIVATE_KEY=
```
4. Enable or not the traditionnal sylius admin form login in your `.env` file:
 ```bash
    SYLIUS_ADMIN_LOGIN=
```

5. Copy Sylius overridden templates to your templates directory (e.g templates/bundles/):

```bash
cp -r vendor/glevents/sylius-admin-saml-plugin/src/templates/bundles/* templates/bundles/
```

You are now ready to go  ! 🚀

## Credits

Developed by [GL Events](https://gl-events.com/).
