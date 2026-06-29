<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\Controller;

use GlEvents\SyliusAdminSamlPlugin\Provider\SamlConfigProvider;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class SamlMetadataAction extends AbstractController
{
    public function __construct(
        private readonly SamlConfigProvider $samlConfigProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            $settings = new Settings($this->samlConfigProvider->getConfig(), true);
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);
        } catch (Error $e) {
            $this->logger->error('Unable to generate SAML SP metadata', ['exception' => $e]);

            return new Response('Unable to generate SAML metadata', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ([] !== $errors) {
            $this->logger->error('Invalid SAML SP metadata', ['errors' => $errors]);

            return new Response('Invalid SAML metadata: ' . implode(', ', $errors), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response($metadata, Response::HTTP_OK, ['Content-Type' => 'text/xml']);
    }
}
