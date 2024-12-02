<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use App\Model\ArticleRepository;
use App\Model\FileRepository;

final class HomepagePresenter extends BasePresenter
{
	public function __construct(
		private readonly ArticleRepository $articleRepository,
		private readonly FileRepository $fileRepository,
	)
	{
        parent::__construct();
    }

	public function renderDefault()
	{
		$this->template->articles = $this->articleRepository->findArticles()->fetchAll();
		$this->template->galleryMedia = $this->fileRepository->getFileList();
	}
}
