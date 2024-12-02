<?php

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\Selection;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\User;
use Nette\Utils\Strings;

class NavigationRepository
{
    public const
        TABLE_PRIMARY = 'navigation',
        COLUMN_PRIMARY_ID = 'id',
        COLUMN_PRIMARY_SORT = 'sort',
        COLUMN_PRIMARY_PARENT = 'parent',
        COLUMN_PRIMARY_USER_ID = 'user_id',
        COLUMN_PRIMARY_DATE_CREATED = 'date_created',
        COLUMN_PRIMARY_DATE_UPDATED = 'date_updated',
        TABLE_TRANSLATION = 'navigation_translation',
        COLUMN_TRANSLATION_PARENT = 'navigation_id',
        COLUMN_TRANSLATION_TITLE = 'title',
        COLUMN_TRANSLATION_LOCALE = 'locale',
        COLUMN_TRANSLATION_SLUG = 'slug',
        COLUMN_TRANSLATION_ID = 'id';

    public function __construct(
        private Explorer $database,
        private User $user,
    ) {}

    public function findAll(): Selection
    {
        return $this->database->table(self::TABLE_PRIMARY);
    }

    public function findAllTranslations(): Selection
    {
        return $this->database->table(self::TABLE_TRANSLATION);
    }

    public function getTreeData(): array
    {
        $translations = $this->findAllTranslations()
            ->fetchAssoc(Utils::createAssocPath(self::COLUMN_TRANSLATION_PARENT, self::COLUMN_TRANSLATION_ID));
        return array_values(
            array_map(
                fn($row) => $row->toArray() +
                    [
                        self::COLUMN_TRANSLATION_TITLE => array_reduce(
                            $translations[$row->id],
                            fn($carry, $translation) => array_merge(
                                $carry,
                                [$translation[self::COLUMN_TRANSLATION_LOCALE] => $translation[self::COLUMN_TRANSLATION_TITLE]]
                            ),
                            []
                        )
                    ],
                $this->findAll()
                    ->select(Utils::createPath(self::COLUMN_PRIMARY_ID, self::COLUMN_PRIMARY_PARENT))
                    ->order(self::COLUMN_PRIMARY_SORT)
                    ->fetchAll()
            )
        );
    }

    public function saveTreeData($data = null): void
    {
        $this->database->beginTransaction();
        try {
            // delete missing ids
            $existingIds = array_filter(array_map(fn($i) => $i->id, $data), fn($id) => is_integer($id));
            if (count($existingIds) > 0) {
                $this->findAll()->where(self::COLUMN_PRIMARY_ID . " NOT IN ?", $existingIds)->delete();
            }

            // insert new items with string id
            $data = array_map(
                function ($item) {
                    if (is_string($item->id)) {
                        $id = $this->findAll()->insert(
                            [
                                self::COLUMN_PRIMARY_USER_ID => $this->user->getId()
                            ]
                        )->id;
                        $this->findAllTranslations()
                            ->insert(
                                array_map(
                                    fn($locale) => [
                                        self::COLUMN_TRANSLATION_LOCALE => $locale,
                                        self::COLUMN_TRANSLATION_TITLE => $item->title->{$locale},
                                        self::COLUMN_TRANSLATION_SLUG => Strings::webalize($item->title->{$locale}),
                                        self::COLUMN_TRANSLATION_PARENT => $id,
                                    ],
                                    array_keys((array)$item->title)
                                )
                            );
                        $item->numId = $id;
                    } else {
                        $item->numId = $item->id;
                    }
                    return $item;
                },
                $data
            );
            // update
            foreach ($data as $sort => $item) {
                $parent = current(
                    $item->parent === null ? [(object)['numId' => null]] : array_filter(
                        $data,
                        fn($i) => $i->id === $item->parent
                    )
                );
                $this->findAll()
                    ->where(self::COLUMN_PRIMARY_ID, $item->numId)
                    ->update(
                        [
                            self::COLUMN_PRIMARY_SORT => $sort,
                            self::COLUMN_PRIMARY_PARENT => $parent->numId,
                            self::COLUMN_PRIMARY_USER_ID => $this->user->getId()
                        ]
                    );
                foreach ((array)$item->title as $locale => $title) {
                    $this->findAllTranslations()
                        ->where(self::COLUMN_TRANSLATION_LOCALE, $locale)
                        ->where(self::COLUMN_TRANSLATION_PARENT, $item->numId)
                        ->update(
                            [
                                self::COLUMN_TRANSLATION_TITLE => $title,
                                self::COLUMN_TRANSLATION_SLUG => Strings::webalize($title),
                            ]
                        );
                }
            }
            $this->database->commit();
        } catch (UniqueConstraintViolationException  $exception) {
            $this->database->rollBack();
            throw new UniqueConstraintViolationException();
        }
    }
}
