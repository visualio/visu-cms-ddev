<?php

namespace App\FrontModule\Components;

use App\FrontModule\Types\BaseTemplate;

class ChangeLocaleTemplate extends BaseTemplate
{
    public string $locale;
    public string $localeDefault;
    public array $localeNames;
}