<?php

namespace App\AdminModule\Factories;

use App\AdminModule\Forms\FormFactory;
use App\Components\TagManager;
use App\Model\TagRepository;
use App\Services\LocaleService;

class TagManagerFactory
{
    private LocaleService $localeService;
    private TagRepository $tagRepository;
    private DynamicFormFactory $dynamicFormFactory;

    public function __construct(
        TagRepository $tagRepository,
        LocaleService $localeService,
        DynamicFormFactory $dynamicFormFactory
    )
    {
        $this->localeService = $localeService;
        $this->tagRepository = $tagRepository;
        $this->dynamicFormFactory = $dynamicFormFactory;
    }

    public function create(
    )
    {
        return new TagManager(
            $this->tagRepository,
            $this->localeService,
            $this->dynamicFormFactory
        );
    }
}