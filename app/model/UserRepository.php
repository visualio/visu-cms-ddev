<?php

namespace App\Model;

use App\Services\MailService;
use Nette;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;
use Nette\Security\Passwords;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final class UserRepository
{
    public const
        ROLE_ADMIN = 'a',
        ROLE_USER = 'u',
        TABLE_NAME = 'user',
        COLUMN_ID = 'id',
        COLUMN_EMAIL = 'email',
        COLUMN_FIRSTNAME = 'firstname',
        COLUMN_LASTNAME = 'lastname',
        COLUMN_PASSWORD_HASH = 'password',
        COLUMN_IP = 'ip',
        COLUMN_ROLE = 'role',
        COLUMN_DATE_LOGGED = 'date_logged',
        COLUMN_DATE_CREATED = 'date_created',
        COLUMN_DATE_UPDATED = 'date_updated',
        COLUMN_TOKEN = 'token',
        COLUMN_USER_ID = 'user_id';

    public array $roles = [
        self::ROLE_ADMIN => 'Administrátor',
        self::ROLE_USER => 'Uživatel',
    ];

    public function __construct(
        private Explorer $database,
        private Passwords $passwords,
        private MailService $mailService,
        private User $user,
    ) {}

    public function findAll(): Selection
    {
        return $this->database->table(self::TABLE_NAME);
    }

    public function upsert(array $formData, ?int $id = null): int
    {
        self::validateFormData($formData);
        if ($id) {
            $this->findAll()->wherePrimary($id)->update($formData);
            return $id;
        } else {
            $formData[self::COLUMN_TOKEN] = Nette\Utils\Random::generate(64);
            $formData[self::COLUMN_USER_ID] = $this->user->getId();
            $row = $this->findAll()->insert($formData);
            $this->mailService->sendUserNewPassword(
                $formData[self::COLUMN_EMAIL],
                $formData[self::COLUMN_TOKEN]
            );
            return $row->{self::COLUMN_ID};
        }
    }

    public static function validateFormData(array $formData)
    {
        Nette\Utils\Validators::assert($formData[self::COLUMN_EMAIL], 'email');
    }

}
