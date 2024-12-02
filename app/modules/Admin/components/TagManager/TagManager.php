<?php

namespace App\Components;

use App\AdminModule\Factories\DynamicFormFactory;
use App\Model\TagRepository;
use App\Responses\BadRequestResponse;
use App\Services\LocaleService;
use Nette\Application\UI\Control;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Forms\Controls\TextInput;

class TagManager extends Control
{
    public $onDelete;
    public $onCreate;
    public $onChange;
    private $currentLocale;
    private LocaleService $localeService;
    private TagRepository $tagRepository;
    private DynamicFormFactory $formFactory;

    public function __construct(
        TagRepository $tagRepository,
        LocaleService $localeService,
        DynamicFormFactory $formFactory
    ) {
        $this->localeService = $localeService;
        $this->tagRepository = $tagRepository;
        $this->formFactory = $formFactory;
    }

    public function createComponentTagForm(): DynamicForm
    {
        return $this->formFactory->create(
            function (DynamicForm $form) {
                $form->setAjax();
                $form->addTranslation(TagRepository::COLUMN_TRANSLATION_TITLE, fn($caption) => new TextInput($caption), 'Název',);
            },
            function (array $values, ?int $id) {
                $id ? $this->presenter->canUpdate() : $this->presenter->canCreate();
                try {
                    $tagId = $this->tagRepository->createTag($values);
                    if ($this->onCreate) {
                        $this->onCreate($tagId);
                    }
                    $this->redrawControls();
                } catch (UniqueConstraintViolationException $e) {
                    $this->presenter->sendResponse(new BadRequestResponse('Názvy tagů musí být unikátní'));
                }
            },
            ['', 'Vytvořit tag'],
            null,
            true
        );
    }


    public function render()
    {
        $this->template->tags = $this->tagRepository->getAllWithTranslations();
        $this->template->locales = $this->localeService->getLocalesTranslation();
        $this->template->currentLocale = $this->currentLocale ?? $this->localeService->getDefaultLocale();
        $this->template->keys = TagRepository::$translationFields;
        $this->template->render(__DIR__ . '/TagManager.latte');
    }

    public function handleChange()
    {
        $data = json_decode($this->presenter->getHttpRequest()->getRawBody());
        $this->tagRepository->insertOrUpdateTranslation($data->id, $data->locale, $data->key, $data->value);
        if ($this->onChange) {
            $this->onChange($data);
        }
        $this->currentLocale = $data->locale;
        $this->redrawControls();
    }

    public function redrawControls()
    {
        $this->redrawControl('list');
        $this->redrawControl('langTab');
    }

    public function handleDelete(int $id, string $locale)
    {
        $this->tagRepository->deleteTag($id);
        if ($this->onDelete) {
            $this->onDelete();
        }
        $this->currentLocale = $locale;
        $this->redrawControls();
    }

}