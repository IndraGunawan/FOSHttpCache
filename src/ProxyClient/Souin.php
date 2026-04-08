<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient;

use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PrefixCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Souin HTTP cache invalidator.
 *
 * @author Indra Gunawan <hello@indra.my.id>
 */
class Souin extends HttpProxyClient implements ClearCapable, PrefixCapable, PurgeCapable, TagCapable
{
    public const HTTP_METHOD_PURGE = 'PURGE';

    public const API_BASEPATH = '/souin-api/souin';

    public const DEFAULT_HTTP_HEADER_SURROGATE_KEY = 'Surrogate-Key';

    protected function configureOptions(): OptionsResolver
    {
        $resolver = parent::configureOptions();
        $resolver->setDefined('api_basepath');
        $resolver->setNormalizer('api_basepath', static function (Options $options, $value): string {
            return '/'.trim($value ?? self::API_BASEPATH, '/');
        });

        return $resolver;
    }

    public function clear(): static
    {
        $this->queueRequest(
            self::HTTP_METHOD_PURGE,
            $this->options['api_basepath'] . '/flush',
            []
        );

        return $this;
    }

    public function invalidatePrefixes(array $prefixes): static
    {
        if ([] === $prefixes) {
            return $this;
        }

        foreach ($prefixes as $prefix) {
            $this->queueRequest(
                self::HTTP_METHOD_PURGE,
                \sprintf('%s/%s', $this->options['api_basepath'], $prefix),
                []
            );
        }

        return $this;
    }

    public function purge(string $url, array $headers = []): static
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $url, $headers);

        return $this;
    }

    public function invalidateTags(array $tags): static
    {
        if ([] === $tags) {
            return $this;
        }

        $escapedTags = $this->escapeTags($tags);

        $this->queueRequest(
            self::HTTP_METHOD_PURGE,
            $this->options['api_basepath'],
            [self::DEFAULT_HTTP_HEADER_SURROGATE_KEY => implode(', ', $escapedTags)]
        );

        return $this;
    }
}
