<?php

declare(strict_types=1);

namespace App\BaseModule\Presenters;

use App\BaseModule\Templates\BaseTemplate;
use App\Services\ViteAssets;
use Nette\Application\UI\Presenter;
use Nette\Http\Url;
use App\Services\LocaleService;
use Nette\Application\Attributes\Persistent;

/* @property-read BaseTemplate $template */
class BasePresenter extends Presenter
{
    #[Persistent]
    public string $locale;

    private LocaleService $localeService;
    private ViteAssets $viteAssets;

    public function injectLocaleService(
        LocaleService $localeService
    ) {
        $this->localeService = $localeService;
    }

    public function injectViteAssets(
        ViteAssets $viteAssets
    ) {
        $this->viteAssets = $viteAssets;
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->viteAssets = $this->viteAssets;
        $this->template->locale = $this->localeService->getLocale();
        $this->template->localeList = $this->localeService->getLocalesTranslation();
        $this->template->localeDefault = $this->localeService->getDefaultLocale();
    }

    public function getURL()
    {
        $httpRequest = $this->getHttpRequest();
        $rawUrl = $httpRequest->getUrl();
        return new Url($rawUrl);
    }
}
