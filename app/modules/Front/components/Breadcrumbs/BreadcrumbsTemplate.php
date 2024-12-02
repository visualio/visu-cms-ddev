<?php

namespace App\FrontModule\Components;

use App\FrontModule\Types\BaseTemplate;
use Nette\Utils\ArrayHash;

class BreadcrumbsTemplate extends BaseTemplate
{
    public ArrayHash $items;
}