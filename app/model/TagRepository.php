<?php

namespace App\Model;

use App\Services\LocaleService;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\User;
use Nette\Utils\Strings;

class TagRepository
{
	public const
		TABLE_PRIMARY = 'tag',
		COLUMN_PRIMARY_ID = 'id',
		COLUMN_PRIMARY_USER_ID = 'user_id',
		COLUMN_PRIMARY_DATE_CREATED = 'date_created',
		COLUMN_PRIMARY_DATE_UPDATED = 'date_updated',
		TABLE_TRANSLATION = 'tag_translation',
		COLUMN_TRANSLATION_PARENT = 'tag_id',
		COLUMN_TRANSLATION_TITLE = 'title',
		COLUMN_TRANSLATION_LOCALE = 'locale',
		COLUMN_TRANSLATION_SLUG = 'slug',
		COLUMN_TRANSLATION_ID = 'id',
		NEWS_TAG_ID = 246;


	public static array $translationFields = [self::COLUMN_TRANSLATION_TITLE];


	public function __construct(
		private Explorer      $database,
		private LocaleService $localeService,
		private User          $user,
	)
	{
	}

	public function findAll(): Selection
	{
		return $this->database->table(self::TABLE_PRIMARY);
	}

	public function findAllTranslations(): Selection
	{
		return $this->database->table(self::TABLE_TRANSLATION);
	}

	public function getAllWithTranslations(): array
	{
		$translations = $this->findAllTranslations()
			->fetchAssoc(Utils::createAssocPath(self::COLUMN_TRANSLATION_PARENT, self::COLUMN_TRANSLATION_LOCALE));
		return array_map(
			fn($row) => (object)array_merge($row->toArray(), ['translations' => $translations[$row->id] ?? []]),
			$this->findAll()->fetchAll()
		);
	}

	public function getAllTagsWithTranslation(string $locale): Selection
	{
		return $this->findAll()
			->select(
				Utils::createPath(
					['*', self::TABLE_PRIMARY],
					[self::COLUMN_TRANSLATION_LOCALE, self::TABLE_TRANSLATION, true],
					[self::COLUMN_TRANSLATION_TITLE, self::TABLE_TRANSLATION, true],
					[self::COLUMN_TRANSLATION_SLUG, self::TABLE_TRANSLATION, true],
				)
			)
			->where(Utils::createPath([self::COLUMN_TRANSLATION_LOCALE, self::TABLE_TRANSLATION, true]), $locale)
			->order(self::COLUMN_TRANSLATION_TITLE);
	}

	public function getTagsWithTranslationByIds(string $locale, array $tagIds): Selection
	{
		return $this->getAllTagsWithTranslation($locale)
			->where(Utils::createPath([self::COLUMN_PRIMARY_ID, self::TABLE_PRIMARY, false]), $tagIds);
	}

	public function getInputOptions(): array
	{
		return $this->findAll()
			->select(
				Utils::createPath(
					[self::COLUMN_PRIMARY_ID, self::TABLE_PRIMARY],
					[self::COLUMN_TRANSLATION_TITLE, self::TABLE_TRANSLATION, true]
				)
			)
			->where(
				Utils::createPath([self::COLUMN_TRANSLATION_LOCALE, self::TABLE_TRANSLATION, true]),
				$this->localeService->getDefaultLocale()
			)
			->fetchPairs(
				self::COLUMN_PRIMARY_ID,
				self::COLUMN_TRANSLATION_TITLE
			);
	}

	public function deleteTag(int $id): void
	{
		$this->findAll()->wherePrimary($id)->delete();
	}

	public function createTag(array $values): ?int
	{
		[$omit, $translations] = Utils::extractTranslationFields($values, self::$translationFields);
		try {
			$this->database->beginTransaction();
			$row = $this->findAll()->insert(
				[
					self::COLUMN_PRIMARY_USER_ID => $this->user->getId()
				]
			);
			$id = $row->{self::COLUMN_PRIMARY_ID};
			$this->findAllTranslations()->insert(
				array_map(
					fn($locale) => [
						self::COLUMN_TRANSLATION_TITLE => $translations[$locale][self::COLUMN_TRANSLATION_TITLE],
						self::COLUMN_TRANSLATION_LOCALE => $locale,
						self::COLUMN_TRANSLATION_SLUG => Strings::webalize(
							$translations[$locale][self::COLUMN_TRANSLATION_TITLE]
						),
						self::COLUMN_TRANSLATION_PARENT => $id,
					],
					array_keys($translations)
				)
			);
			$this->database->commit();
			return $id;
		} catch (UniqueConstraintViolationException $e) {
			$this->database->rollBack();
			throw new UniqueConstraintViolationException();
		}
	}

	public function insertOrUpdateTranslation(int $tagId, string $locale, string $column, string $value): int
	{
		$row = $this->findAllTranslations()
			->where(self::COLUMN_TRANSLATION_PARENT, $tagId)
			->where(self::COLUMN_TRANSLATION_LOCALE, $locale);
		if ($row->fetch()) {
			$id = $row->update([$column => $value]);
			return $id;
		} else {
			$data = $row->insert(
				[
					self::COLUMN_TRANSLATION_LOCALE => $locale,
					self::COLUMN_TRANSLATION_PARENT => $tagId,
					$column => $value
				]
			);
			return $data->id;
		}
	}

}
