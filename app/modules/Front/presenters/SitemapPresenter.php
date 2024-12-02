<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use App\Model\ArticleRepository;


final class SitemapPresenter extends BasePresenter
{
	public function __construct(
		private ArticleRepository $articleRepository
	)
	{}

	public function renderDefault()
	{
		$this->template->articles = $this->articleRepository->findArticles()->fetchAll();
	}
}
