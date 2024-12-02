<?php

namespace App\Templates;

use App\Filter\FileFilter;
use App\Filter\StaticFilters;
use Nette\Application\UI\Control;
use Nette\Application\UI\Template;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Caching\Storage;
use Nette\Http\Request as HttpRequest;
use Nette\Security\User;

class TemplateFactory extends \Nette\Bridges\ApplicationLatte\TemplateFactory
{
    private FileFilter $fileFilter;

    public function __construct(
        FileFilter $fileFilter,
        LatteFactory $latteFactory,
        HttpRequest $httpRequest = null,
        User $user = null,
        Storage $cacheStorage = null
    ) {
        parent::__construct($latteFactory, $httpRequest, $user, $cacheStorage);
        $this->fileFilter = $fileFilter;
    }

    public function createTemplate(Control $control = null, string $class = null): Template
    {
        $template = parent::createTemplate($control);
        $template->addFilter('join', fn(...$args) => StaticFilters::join(...$args));
        $template->addFilter('nbsp', fn(...$args) => StaticFilters::nbsp(...$args));
        $template->addFilter('ytThumbnail', fn(...$args) => StaticFilters::ytThumbnail(...$args));
        $template->addFilter('image', fn(...$args) => $this->fileFilter->image(...$args));
        $template->addFilter('srcset', fn(...$args) => $this->fileFilter->srcset(...$args));
        $template->addFilter('file', fn(...$args) => $this->fileFilter->file(...$args));
        $template->addFilter('width', fn(...$args) => $this->fileFilter->width(...$args));
        $template->addFilter('height', fn(...$args) => $this->fileFilter->height(...$args));

        $template->SIZE = Types\Size::class;
        $template->VARIANT = Types\Variant::class;
        $template->SEVERITY = Types\Severity::class;
        $template->ORIENTATION = Types\Orientation::class;

        return $template;
    }
}