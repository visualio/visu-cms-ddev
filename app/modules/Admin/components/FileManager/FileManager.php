<?php

namespace App\Components;

use App\AdminModule\Factories\DynamicFormFactory;
use App\Model\FileRepository;
use App\Model\TagRepository;
use App\Model\Utils;
use App\Responses\BadRequestResponse;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Control;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\TextInput;
use Symfony\Component\Config\Definition\Exception\DuplicateKeyException;

class FileManager extends Control
{

    public const FIELD_TAGS = "tags";
    private ?ActiveRow $record = null;
    /* @var callable*/
    public $onUpload;
    /* @var callable*/
    public $onDelete;
    /* @var callable*/
    public $onChange;
    /* @var callable*/
    public $onSelect;
    public ?string $selectTarget = null;

    private TagRepository $tagRepository;
    private FileRepository $fileRepository;
    private DynamicFormFactory $dynamicFormFactory;

    public function __construct(
        FileRepository $fileRepository,
        TagRepository $tagRepository,
        DynamicFormFactory $dynamicFormFactory
    ) {
        $this->fileRepository = $fileRepository;
        $this->tagRepository = $tagRepository;
        $this->dynamicFormFactory = $dynamicFormFactory;
    }

    public function createComponentFileUploader(): Uppy
    {
        return new Uppy(
            '<textarea></textarea>',
            $this->getUniqueId() . 'uppy',
            $this->link('uploadInit!'),
            fn() => null,
            $this->link('uploadSuccess!'),
            null,
            null,
            [],
            true,
        );
    }

    public function createComponentReplaceUploader(): Uppy
    {
        return new Uppy(
            '<textarea></textarea>',
            $this->getUniqueId() . 'uppy',
            $this->link('replaceInit!', $this->record?->{FileRepository::COLUMN_PRIMARY_ID}),
            fn() => null,
            $this->link('replaceSuccess!', $this->record?->{FileRepository::COLUMN_PRIMARY_ID}),
            null,
            null,
            [],
            false,
        );
    }

    public function createComponentVideoForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
            function (DynamicForm $form) {
                $form->setAjax();
                $form->addRadioList('type', 'Služba',
                                    [
                                        FileRepository::FILE_TYPE_YOUTUBE => 'Youtube',
                                        FileRepository::FILE_TYPE_VIMEO => 'Vimeo'
                                    ]
                )
                    ->setRequired('Typ není vyplněný.')
                    ->setDefaultValue(FileRepository::FILE_TYPE_YOUTUBE);
                $form->addText('link', 'Odkaz')
                    ->setRequired('Odkaz není vyplněný.');
            },
            function (array $values) {
                try {
                    $this->fileRepository->insertEmbedVideo($values['link'], $values['type']);
                    if ($this->onUpload) {
                        $this->onUpload();
                    }
                    $this->redrawFileList();
                } catch (BadRequestException $e) {
                    $this->presenter->sendResponse(new BadRequestResponse('Odkaz má nesprávný tvar'));
                } catch (\PDOException | DuplicateKeyException $e) {
                    $this->presenter->sendResponse(new BadRequestResponse('Video již existuje'));
                }
            },
            'video',
            null,
            true
        );
    }

    public function createComponentImageCompareForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
            function (DynamicForm $form) {
                $form->setAjax();
                $form->addGroup(null, 2);
                $form->addImageUpload('left', 'Fotka vlevo')
                    ->setRequired('Chybí fotka vlevo');
                $form->addImageUpload('right', 'Fotka vpravo')
                    ->setRequired('Chybí fotka vpravo');
            },
            function (array $values) {
                $this->fileRepository->insertComparison($values['left'], $values['right']);
                if ($this->onUpload) {
                    $this->onUpload();
                }
                $this->redrawFileList();
            },
            'srovnání',
            null,
            true
        );
    }

    public function createComponentFileForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
            function (DynamicForm $form) {
                $form->setAjax();
                $form->addImageUpload(
                    FileRepository::COLUMN_PRIMARY_THUMBNAIL,
                    "Náhled pro web",
                );
                $form->addTranslation(
                    FileRepository::COLUMN_TRANSLATION_TITLE,
                    fn(string $caption) => new TextInput($caption),
                    "Název"
                );
                $form->addTranslation(
                    FileRepository::COLUMN_TRANSLATION_DESCRIPTION,
                    fn(string $caption) => new TextInput($caption),
                    "Popis"
                );
                $form->addHidden(FileRepository::COLUMN_PRIMARY_ID);
                $form->addMultiSelect(self::FIELD_TAGS, "Tagy", $this->tagRepository->getInputOptions());
            },
            function (array $values) {
                [$values, $translations] = Utils::extractTranslationFields($values, FileRepository::$translationFields);
                [$omit, $thumb, $id, $tags] = Utils::extractValues(
                    $values,
                    FileRepository::COLUMN_PRIMARY_THUMBNAIL,
                    FileRepository::COLUMN_PRIMARY_ID,
                    self::FIELD_TAGS,
                );
                $this->fileRepository->updateFile($id, $translations, $tags ?? [], $thumb);
                if ($this->onChange) {
                    $this->onChange();
                }
                $this->finishEdit();
            },
            'soubor',
            $this->record ? array_merge(
                $this->record->toArray(),
                [
                    self::FIELD_TAGS => $this->fileRepository->findFileTags()
                        ->where(FileRepository::COLUMN_TAG_FILE, $this->record->id)
                        ->fetchPairs(null, FileRepository::COLUMN_TAG_PARENT)
                ] + Utils::createTranslationFields(
                    $this->fileRepository->findFileTranslations()
                        ->where(FileRepository::COLUMN_TRANSLATION_PARENT, $this->record->id),
                    FileRepository::$translationFields
                )
            ) : null
        );

//        $form->addHidden(FileRepository::COLUMN_PRIMARY_ID);
//        $form->addSubmit("submit", "Upravit soubor");
//        $form->setSaveHandler(
//            function (ArrayHash $values, \stdClass $translations) {
//                [$omit, $id, $thumb, $tags] = Utils::extractValues(
//                    $values,
//                    FileRepository::COLUMN_PRIMARY_ID,
//                    FileRepository::COLUMN_PRIMARY_THUMBNAIL,
//                    self::FIELD_TAGS,
//                );
//                $this->fileRepository->updateFile($id, $translations, $thumb, $tags);
//                if ($this->onChange) $this->onChange();
//                $this->finishEdit();
//            }
//        );
//        return $form;
    }

    public function render()
    {
        $this->template->selectTarget = $this->selectTarget;
        $this->template->canSelect = !!$this->onSelect;
        $this->template->record = $this->record;
        $this->template->files = $this->fileRepository->getFileList();
        $this->template->filterTags = $this->fileRepository->getDistinctTags();
        $this->template->getFileLink = fn(ActiveRow $file) => $this->fileRepository->getFileLink($file);
        $this->template->render(__DIR__ . '/FileManager.latte');
    }

    public function handleUploadInit(): void
    {
        $files = $this->presenter->getHttpRequest()->getFiles()["files"];
        foreach ($files as $file) {
            $this->fileRepository->handleUpload($file, null);
        }
    }

    public function handleReplaceInit(int $id): void
    {
        [$file] = $this->presenter->getHttpRequest()->getFiles()["files"];
        $this->fileRepository->handleUpload($file, $id);
        $this->handleInitEdit($id);
    }

    public function handleReplaceSuccess(int $id): void
    {
        $this->handleInitEdit($id);
        $this->redrawFileList();
    }

    public function handleUploadSuccess(): void
    {
        if ($this->onUpload) {
            $this->onUpload();
        }
        $this->redrawFileList();
    }

    public function handleSelect()
    {
        if ($this->onSelect) {
            $fileIds = $this->presenter->getHttpRequest()->getPost("file");
            $selectTarget = $this->presenter->getHttpRequest()->getPost("selectTarget");
            if (is_array($fileIds) && $selectTarget) {
                $this->onSelect($fileIds, $selectTarget);
            }
        }
        $this->redrawFileList();
    }

    public function handleInitEdit(int $id)
    {
        $record = $this->fileRepository->findFiles()->wherePrimary($id)->fetch();
        if (!$record) {
            $this->presenter->sendResponse(new BadRequestResponse('Soubor neexistuje'));
        }
        $this->record = $record;
        $this->redrawFileForm();
    }

    public function handleDelete(int $id)
    {
        $this->fileRepository->deleteFile($id);
        if ($this->onDelete) {
            $this->onDelete($id);
        }
        $this->finishEdit();
    }

    private function redrawFileList()
    {
        $this->redrawControl('fileList');
    }

    private function redrawFileForm()
    {
        $this->redrawControl('fileForm');
    }

    private function finishEdit()
    {
        $this->record = null;
        $this->redrawFileList();
        $this->redrawFileForm();
    }
}