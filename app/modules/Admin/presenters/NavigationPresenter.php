<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Factories\DynamicFormFactory;
use App\AdminModule\Forms\FormFactory;
use App\Components\DynamicForm;
use App\Model;
use App\Model\NavigationRepository;
use App\Responses\BadRequestResponse;
use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

final class NavigationPresenter extends BasePresenter
{
    private NavigationRepository $navigationRepository;
    private ?ActiveRow $record = null;
    private DynamicFormFactory $dynamicFormFactory;

    public function __construct(
        NavigationRepository $navigationRepository,
        DynamicFormFactory $dynamicFormFactory
    ) {
        parent::__construct();
        $this->navigationRepository = $navigationRepository;
        $this->dynamicFormFactory = $dynamicFormFactory;
    }

    public function renderDefault()
    {
        $this->template->data = $this->navigationRepository->getTreeData();
    }

    public function actionEdit(?int $id = null)
    {
        $this->record = $id ? $this->navigationRepository->findAll()->wherePrimary($id)->fetch() : null;
        if ($id && !$this->record) {
            throw new BadRequestException('Záznam neexistuje');
        }
    }


    public function handleSave()
    {
        try {
            $treeData = Json::decode($this->getHttpRequest()->getRawBody());
            $this->navigationRepository->saveTreeData($treeData);
            $this->sendJson(['message' => 'Navigace byla úspěšně upravena']);
        } catch (JsonException $e) {
            $this->sendResponse(new BadRequestResponse('Neočekávaná chyba'));
        } catch (UniqueConstraintViolationException $exception) {
            $this->sendResponse(new BadRequestResponse('Některé položky mají duplicitní názvy'));
        }
    }

    public function createComponentNavigationForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
            function (DynamicForm $form) {
            },
            function (array $values) {
            },
            "položku",
            null
        );
//        $caption = $this->record ? 'Upravit položku' : 'Vytvořit položku';
//        $form = $this->formFactory->create($caption);
//        $form->addTranslation(
//            NavigationRepository::COLUMN_TRANSLATION_TITLE,
//            fn($caption) => new TextInput($caption),
//            'Název'
//        );
//        $form->addTranslation(
//            NavigationRepository::COLUMN_TRANSLATION_SLUG,
//            fn($caption) => new TextInput($caption),
//            'URL'
//        );
//        $form->addSubmit('submit', $caption);
//
//        $form->setSaveHandler(
//          function ($values, $translations) {
//              if ($this->record) {
//                  $this->checkPrivilege('update');
//                  try {
//                      foreach ($translations as $key => $translation) {
//                          $this->navigationRepository
//                              ->findAllTranslations()
//                              ->where(NavigationRepository::COLUMN_TRANSLATION_PARENT, $this->record->id)
//                              ->where(NavigationRepository::COLUMN_TRANSLATION_LOCALE, $key)
//                              ->update($translation);
//                      }
//                      $this->flashMessage('Záznam byl úspěšně upraven');
//                  } catch (UniqueConstraintViolationException $e) {
//                      $this->flashMessage('URL musí být unikátní', 'warning');
//                  }
//                  $this->redirect('edit', $this->record->id);
//              }
//          }
//        );
//
//        if ($this->record) {
//            $form->setDefaultValues(
//                [],
//                $this->navigationRepository
//                    ->findAllTranslations()
//                    ->where(NavigationRepository::COLUMN_TRANSLATION_PARENT, $this->record->id)
//                    ->fetchAssoc('locale')
//            );
//        }
//
//        return $form;
    }

}
