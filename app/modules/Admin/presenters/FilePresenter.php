<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Factories\FileManagerFactory;
use App\Components\FileManager;
use App\Model;
use App\Services\TusService;

final class FilePresenter extends BasePresenter
{
    private Model\FileRepository $fileRepository;
    private FileManagerFactory $fileManagerFactory;
    private TusService $tusService;

    public function __construct(
        Model\FileRepository $fileRepository,
        FileManagerFactory $fileManagerFactory,
        TusService $tusService
    )
    {
        parent::__construct();
        $this->fileRepository = $fileRepository;
        $this->fileManagerFactory = $fileManagerFactory;
        $this->tusService = $tusService;
    }

    public function actionUploadFile(?string $id)
    {
        $this->tusService->sendResponse($this->link('uploadFile'));
    }

    public function createComponentFileManager(): FileManager
    {
        return $this->fileManagerFactory->create();
    }

}
