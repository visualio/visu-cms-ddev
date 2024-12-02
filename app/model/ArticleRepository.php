<?php

namespace App\Model;

use App\Services\FileStorage;
use Exception;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class ArticleRepository
{
    public const
        TABLE_PRIMARY = 'article',
        COLUMN_PRIMARY_ID = 'id',
        COLUMN_PRIMARY_SORT = 'sort',
        COLUMN_PRIMARY_VIDEO = 'video',
        COLUMN_PRIMARY_IS_VISIBLE = 'is_visible',
        COLUMN_PRIMARY_IMAGE_POS = 'image_position',
        COLUMN_PRIMARY_IMAGE = 'image',
        COLUMN_PRIMARY_USER_ID = 'user_id',
        COLUMN_PRIMARY_DATE_CREATED = 'date_created',
        COLUMN_PRIMARY_DATE_UPDATED = 'date_updated',
        TABLE_TRANSLATION = 'article_translation',
        COLUMN_TRANSLATION_PARENT = 'article_id',
        COLUMN_TRANSLATION_TITLE = 'title',
        COLUMN_TRANSLATION_TEXT = 'text',
        COLUMN_TRANSLATION_PEREX = 'perex',
        COLUMN_TRANSLATION_LOCALE = 'locale',
        COLUMN_TRANSLATION_SLUG = 'slug',
        COLUMN_TRANSLATION_ID = 'id',
        TABLE_FILE = 'article_has_file',
        COLUMN_FILE_PARENT = 'article_id',
        COLUMN_FILE_ID = 'file_id',
        TABLE_TAG = 'article_has_tag',
        COLUMN_TAG_PARENT = 'article_id',
        COLUMN_TAG_ID = 'tag_id',
        IMAGE_NAMESPACE = 'article';

    public static array $translationFields = [self::COLUMN_TRANSLATION_TITLE, self::COLUMN_TRANSLATION_TEXT, self::COLUMN_TRANSLATION_PEREX];

    public function __construct(
        private Explorer $database,
        private FileStorage $fileStorage,
    ) {}

    public function findArticles(): Selection
    {
        return $this->database->table(self::TABLE_PRIMARY);
    }

    public function findArticleById(int $id): Selection
    {
        return $this->findArticles()->wherePrimary($id);
    }

    public function findArticleTranslations(): Selection
    {
        return $this->database->table(self::TABLE_TRANSLATION);
    }

    public function findArticleFiles(): Selection
    {
        return $this->database->table(self::TABLE_FILE);
    }

    public function findArticleTags(): Selection
    {
        return $this->database->table(self::TABLE_TAG);
    }

    public function getAllArticlesWithTranslation(string $locale): Selection
    {
        return $this->findArticles()
            ->select(
                Utils::createPath(
                    ['*', self::TABLE_PRIMARY],
                    [self::COLUMN_TRANSLATION_TITLE, self::TABLE_TRANSLATION, true],
                    [self::COLUMN_TRANSLATION_PEREX, self::TABLE_TRANSLATION, true],
                    [self::COLUMN_TRANSLATION_TEXT, self::TABLE_TRANSLATION, true],
                    [self::COLUMN_TRANSLATION_SLUG, self::TABLE_TRANSLATION, true],
                )
            )
            ->where(Utils::createPath([self::COLUMN_TRANSLATION_LOCALE, self::TABLE_TRANSLATION, true]), $locale)
            ->order(self::COLUMN_PRIMARY_SORT);
    }

    public function getArticleFiles(int $articleId): array
    {
        return $this->findArticleFiles()
            ->where(self::COLUMN_FILE_PARENT, $articleId)
            ->fetchPairs(self::COLUMN_FILE_ID, self::COLUMN_FILE_ID);
    }

    public function getArticleTags(int $articleId): array
    {
        return $this->findArticleTags()
            ->where(self::COLUMN_FILE_PARENT, $articleId)
            ->fetchPairs(self::COLUMN_TAG_ID, self::COLUMN_TAG_ID);
    }

    public function getVisibleArticlesWithTranslation(string $locale): Selection
    {
        return $this->getAllArticlesWithTranslation($locale)
            ->where(self::COLUMN_PRIMARY_IS_VISIBLE, true);
    }

    public function updateArticle(int $id, array $values, array $translations, array $files, ?array $tags = null): void
    {
        $this->findArticleById($id)->update($values);
        foreach ((array)$translations as $locale => $data) {
            $this->findArticleTranslations()
                ->where(self::COLUMN_TRANSLATION_PARENT, $id)
                ->where(self::COLUMN_TRANSLATION_LOCALE, $locale)
                ->update(
                    [self::COLUMN_TRANSLATION_SLUG => Strings::webalize($data[self::COLUMN_TRANSLATION_TITLE])] + $data
                );
        }
        $this->findArticleFiles()->where(self::COLUMN_FILE_PARENT, $id)->delete();
        $this->createArticleFiles($id, $files);
        $this->findArticleTags()->where(self::COLUMN_TAG_PARENT, $id)->delete();
        $this->createArticleTags($id, $tags);
    }

    public function createArticle(array $values, array $translations, array $files, ?array $tags = null): ActiveRow
    {
        $row = $this->findArticles()->insert($values);
        foreach ((array)$translations as $locale => $data) {
            $this->findArticleTranslations()
                ->insert(
                    [
                        self::COLUMN_TRANSLATION_SLUG => Strings::webalize($data[self::COLUMN_TRANSLATION_TITLE]),
                        self::COLUMN_TRANSLATION_LOCALE => $locale,
                        self::COLUMN_TRANSLATION_PARENT => $row->id
                    ] + $data
                );
        }
        $this->createArticleFiles($row->id, $files);
        $this->createArticleTags($row->id, $tags);
        return $row;
    }

    private function createArticleFiles(int $articleId, array $fileIds): void
    {
        $fileIds = array_values(array_filter($fileIds));
        if (count($fileIds) > 0) {
            $this->findArticleFiles()
                ->insert(
                    array_map(
                        fn($fileId) => [self::COLUMN_FILE_PARENT => $articleId, self::COLUMN_FILE_ID => (int) $fileId],
                        $fileIds
                    )
                );
        }
    }

    private function createArticleTags(int $articleId, ?array $tagIds = []): void
    {
        if ($tagIds && count($tagIds) > 0) {
            $this->findArticleTags()
                ->insert(
                    array_map(
                        fn($tagId) => [self::COLUMN_TAG_PARENT => $articleId, self::COLUMN_TAG_ID => $tagId],
                        $tagIds
                    )
                );
        }
    }

    public function deleteArticle(int $id): void
    {
        $row = $this->findArticleById($id);
        $article = $row->fetch();
        if (!$article) {
            throw new Exception('Record does not exist');
        }
        $this->fileStorage->setNamespace(self::IMAGE_NAMESPACE);
        $this->fileStorage->removeFile($article->image);
        $row->delete();
    }

    public function updateArticleSort(array $articleIds): void
    {
        foreach ($articleIds as $sort => $articleId) {
            $this->findArticles()
                ->wherePrimary($articleId)
                ->update([self::COLUMN_PRIMARY_SORT => $sort]);
        }
    }

}
