<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Factories\DynamicFormFactory;
use App\AdminModule\Factories\FileManagerFactory;
use App\AdminModule\Factories\TagManagerFactory;
use App\Components\DataGrid;
use App\Components\DynamicForm;
use App\Components\FileManager;
use App\Components\TagManager;
use App\Model\ArticleRepository;
use App\Model\FileRepository;
use App\Model\TagRepository;
use App\Model\Utils;
use App\Services\FileStorage;
use App\Services\ImageService;
use App\Services\LocaleService;
use App\Services\TusService;
use Exception;
use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;

final class ArticlePresenter extends BasePresenter
{
    const FIELD_FILES = 'files';
    const FIELD_TAGS = 'tags';
    const FIELD_VIDEO = 'video';

    private ?ActiveRow $record = null;

    private ArticleRepository $articleRepository;
    private FileStorage $fileStorage;
    private TusService $tusService;
    private LocaleService $localeService;
    private FileManagerFactory $fileManagerFactory;
    private FileRepository $fileRepository;
    private TagManagerFactory $tagManagerFactory;
    private TagRepository $tagRepository;
    private DynamicFormFactory $dynamicFormFactory;

    public function __construct(
        ArticleRepository $articleRepository,
        FileStorage $fileStorage,
        TusService $tusService,
        LocaleService $localeService,
        FileManagerFactory $fileManagerFactory,
        FileRepository $fileRepository,
        TagManagerFactory $tagManagerFactory,
        TagRepository $tagRepository,
        DynamicFormFactory $dynamicFormFactory
    ) {
        parent::__construct();
        $this->articleRepository = $articleRepository;
        $this->fileStorage = $fileStorage;
        $this->fileStorage->setNamespace(ArticleRepository::IMAGE_NAMESPACE);
        $this->tusService = $tusService;
        $this->localeService = $localeService;
        $this->fileManagerFactory = $fileManagerFactory;
        $this->fileRepository = $fileRepository;
        $this->tagManagerFactory = $tagManagerFactory;
        $this->tagRepository = $tagRepository;
        $this->dynamicFormFactory = $dynamicFormFactory;
    }

    public function actionEdit(?int $id = null)
    {
        $this->record = empty($id) ? null : $this->articleRepository->findArticleById($id)->fetch();
        if ($id && !$this->record) {
            throw new BadRequestException('Záznam neexistuje');
        }
    }

    public function actionShow(int $id): void
    {
        $this->articleRepository->findArticleById($id)->update([ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE => true]);
        $this->flashMessage('Článek byl publikován');
        $this->redirect('default');
    }

    public function actionHide(int $id): void
    {
        $this->articleRepository->findArticleById($id)->update([ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE => false]);
        $this->flashMessage('Článek byl skryt');
        $this->redirect('default');
    }

    public function actionDelete(int $id): void
    {
        try {
            $this->articleRepository->deleteArticle($id);
            $this->flashMessage("Článek byl smazán.");
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'warning');
        }
        $this->redirect('default');
    }

    public function handleSortArticles(?string $idList)
    {
        $this->articleRepository->updateArticleSort(explode(",", $idList));
    }

    public function createComponentGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name, 'sortArticles!');
        $grid->setDataSource($this->articleRepository->getAllArticlesWithTranslation($this->localeService->getDefaultLocale()));
        $grid->addColumnLink(ArticleRepository::COLUMN_TRANSLATION_TITLE, 'Titulek', 'edit');
        $grid->addFilterText(ArticleRepository::COLUMN_TRANSLATION_TITLE, 'Titulek');
        $grid->addColumnDateTime(ArticleRepository::COLUMN_PRIMARY_DATE_CREATED, 'Datum')
            ->setFormat('j. n. Y')
            ->setSortable();
        $grid->addColumnText(ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE, 'Publikováno?')
            ->setReplacement([1 => 'Ano', 0 => 'Ne'])
            ->setSortable();
        $grid->addFilterSelect(ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE, 'Publikováno?', [1 => 'Ano', 0 => 'Ne']);
        $grid->addEditAction();
        $grid->addDeleteAction(ArticleRepository::COLUMN_TRANSLATION_TITLE);
        $grid->addIconAction('show', 'Zobrazit', 'check');
        $grid->addIconAction('hide', 'Skrýt', 'close');
        return $grid;
    }


    public function createComponentFileManager(): FileManager
    {
        $manager = $this->fileManagerFactory->create();
        $redrawFiles = function () {
            $fileSelect = $this['articleForm']
                ->getInput(self::FIELD_FILES)
                ->setItems($this->fileRepository->getInputOptions());
            $this->payload->snippets = [
                $fileSelect->getHtmlId() => (string)$fileSelect->getControl()
            ];
        };
        $manager->onUpload[] = $redrawFiles;
        $manager->onDelete[] = $redrawFiles;
        $manager->onChange[] = $redrawFiles;
        return $manager;
    }

    public function createComponentTagManager(): TagManager
    {
        $manager = $this->tagManagerFactory->create();
        $redrawFiles = function () {
            $tagSelect = $this['articleForm']
                ->getInput(self::FIELD_TAGS)
                ->setItems($this->tagRepository->getInputOptions());
            $this->payload->snippets = [
                $tagSelect->getHtmlId() => (string)$tagSelect->getControl()
            ];
        };
        $manager->onChange[] = $redrawFiles;
        $manager->onCreate[] = $redrawFiles;
        $manager->onDelete[] = $redrawFiles;
        return $manager;
    }

    public function createComponentArticleForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
          function (DynamicForm $form) {
              $form->setAjax();
              $form->addImageUpload(ArticleRepository::COLUMN_PRIMARY_IMAGE, 'Úvodní obrázek');
              $form->addFileUpload(self::FIELD_VIDEO, 'Video')
                  ->setRestrictions(['allowedFileTypes' => FileRepository::VIDEO_MIME_TYPES]);
              $form->addMultiSelect(self::FIELD_FILES, 'Soubory', $this->fileRepository->getInputOptions());
              $form->addButton('openFileManager', 'Spravovat soubory')
                  ->setHtmlAttribute('uk-toggle', 'target: #fileManagerModal')
                  ->setOmitted();
              $form->addMultiSelect(self::FIELD_TAGS, 'Tagy', $this->tagRepository->getInputOptions());
              $form->addButton('openTagManager', 'Spravovat tagy')
                  ->setHtmlAttribute('uk-toggle', 'target: #tagManagerModal')
                  ->setOmitted();
              $form->addTranslation(
                  ArticleRepository::COLUMN_TRANSLATION_TITLE,
                  fn($caption) => (new TextInput($caption)),
                  'Titulek'
              );
              $form->addTranslation(
                  ArticleRepository::COLUMN_TRANSLATION_PEREX,
                  fn($caption) => new TextInput($caption),
                  'Perex'
              );
              $form->addTranslation(
                  ArticleRepository::COLUMN_TRANSLATION_TEXT,
                  fn($caption) => $form->textAreaToWysiwyg((new TextArea($caption))),
                  'Text'
              );
              $form->addText(ArticleRepository::COLUMN_PRIMARY_DATE_CREATED, 'Datum')
                  ->setHtmlAttribute('class', 'js-date');
              $form->addCheckbox(ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE, 'Je článek viditelný?')
                  ->setDefaultValue(true);
              $form->addMultiplier(
                  'showcase',
                  function (Container $container) {
                      $container->addText('title', 'Název')
                          ->setOption('class', 'uk-width-1-2');
                      $container->addTranslation(
                          'translation',
                          fn($caption) => new TextInput($caption),
                          'Překlad',
                          'uk-width-1-2'
                      );
                      $container->addThumbnailUpload('pdf', 'Soubory', true)
                          ->setOption('class', 'uk-width-1-1');
                      return $container;
                  },
                  ["článek", "článek"]
              );
          },
            function (array $values, ?int $id) {
                $id ? $this->canUpdate() : $this->canCreate();
                [$values, $translations] = Utils::extractTranslationFields($values, ArticleRepository::$translationFields);
                [$values] = $this->fileStorage->uploadFormFiles($values);
                [$values, $files, $tags, $omit] = Utils::extractValues($values, self::FIELD_FILES, self::FIELD_TAGS, "showcase");
                if ($id) {
                    $this->articleRepository->updateArticle($id, $values, $translations, $files, $tags);
                    $this->flashMessage('Záznam byl úspěšně upraven');
                } else {
                    $id = $this->articleRepository->createArticle($values, $translations, $files, $tags)->id;
                    $this->flashMessage('Záznam byl úspěšně vytvořen');
                }
                $this->redirect('edit', $this->record->id ?? $id);
            },
            "článek",
            $this->record ? $this->record->toArray() + [
                    self::FIELD_FILES => $this->articleRepository->getArticleFiles($this->record->id),
                    self::FIELD_TAGS => $this->articleRepository->getArticleTags($this->record->id),
                ] + Utils::createTranslationFields(
                    $this->articleRepository
                        ->findArticleTranslations()
                        ->where(ArticleRepository::COLUMN_TRANSLATION_PARENT, $this->record->id),
                    ArticleRepository::$translationFields
                ) : null
        );
    }
}
