<?php

namespace App\AdminModule\Factories;


use App\Components\DynamicForm;
use App\Services\FileStorage;
use App\Services\LocaleService;
use App\Services\TusService;
use Nette\Http\Session;

class DynamicFormFactory
{
    private LocaleService $localeService;
    private FileStorage $fileStorage;
    private TusService $tusService;
    private Session $session;

    public function __construct(
        LocaleService $localeService,
        FileStorage $fileStorage,
        TusService $tusService,
        Session $session
    )
    {
        $this->localeService = $localeService;
        $this->fileStorage = $fileStorage;
        $this->tusService = $tusService;
        $this->session = $session;
    }

    public function create(
        callable $onRender,
        callable $onSubmit,
        $caption = null,
        ?array $defaults = null,
        ?bool $isCompact = false
    )
    {
        return new DynamicForm(
            $onRender,
            $onSubmit,
            $this->localeService,
            $this->fileStorage,
            $this->tusService,
            $this->session,
            $defaults,
            $caption,
            $isCompact
        );
    }
}