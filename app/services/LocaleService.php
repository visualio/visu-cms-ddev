<?php

namespace App\Services;

use Contributte\Translation\Translator;
use Contributte\Translation\LocalesResolvers\Router as RouterResolver;
use JetBrains\PhpStorm\Pure;

class LocaleService
{
    private Translator $translator;
    private RouterResolver $routerResolver;

    public const LOCALES_TRANSLATION = [
        'cs' => 'Čeština',
        'en' => 'Angličtina',
    ];

    public const LOCALE_NAMES = [
        'cs' => 'CZ',
        'en' => 'EN',
    ];

    public function __construct(
        Translator $translator,
        RouterResolver $routerResolver
    ) {
        $this->translator = $translator;
        $this->routerResolver = $routerResolver;
        $locale = $this->routerResolver->resolve($this->translator);
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    #[Pure] public function getDefaultLocale(): string
    {
        return $this->translator->getDefaultLocale();
    }

    public function translate(string $message, ...$args): string
    {
        return $this->translator->translate($message, ...$args);
    }

    public function getLocaleList(): array
    {
        return array_map(
            fn($lang) => substr($lang, 0, 2),
            $this->translator->getLocalesWhitelist()
        );
    }

    public function getLocaleNames(): array
    {
        return array_combine(
            $this->translator->getLocalesWhitelist(),
            array_map(fn($key) => self::LOCALE_NAMES[$key] ?? $key, $this->translator->getLocalesWhitelist())
        );
    }

    public function getLocalesTranslation(): array
    {
        return array_combine(
            $this->translator->getLocalesWhitelist(),
            array_map(fn($key) => self::LOCALES_TRANSLATION[$key] ?? $key, $this->translator->getLocalesWhitelist())
        );
    }
}