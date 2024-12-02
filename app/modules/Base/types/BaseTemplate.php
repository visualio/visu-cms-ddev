<?php

namespace App\BaseModule\Templates;

use App\Services\ViteAssets;
use Latte\Runtime\Template;

class BaseTemplate extends \Nette\Bridges\ApplicationLatte\Template
{
    public ViteAssets $viteAssets;
    public string $SIZE;
    public string $ORIENTATION;
    public string $SEVERITY;
    public string $VARIANT;
    public string $baseUrl;
    public string $baseUri;
    public Template $this;
    public string $locale;
}