<?php


namespace App\Services;

use App\Model\UserRepository;
use DateTime;
use Nette\Database\Explorer;
use Nette\Security\AuthenticationException;
use Nette\Security\SimpleIdentity;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;

class Authenticator implements \Nette\Security\Authenticator
{
    private Explorer $database;
    private Passwords $passwords;

    public function __construct(
        Explorer $database,
        Passwords $passwords
    ) {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    public function authenticate(string $username, string $password): IIdentity
    {

        $row = $this->database->table(UserRepository::TABLE_NAME)
            ->where(UserRepository::COLUMN_EMAIL, $username)
            ->fetch();

        if (!$row || !$this->passwords->verify($password, $row[UserRepository::COLUMN_PASSWORD_HASH])) {
            throw new AuthenticationException('Invalid credentials', self::INVALID_CREDENTIAL);
        }

        if ($this->passwords->needsRehash($row[UserRepository::COLUMN_PASSWORD_HASH])) {
            $row->update(
                [
                    UserRepository::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
                ]
            );
        }

        $row->update(
            [
                UserRepository::COLUMN_IP => $_SERVER["REMOTE_ADDR"],
                UserRepository::COLUMN_DATE_LOGGED => new DateTime(),
            ]
        );

        $arr = $row->toArray();
        unset($arr[UserRepository::COLUMN_PASSWORD_HASH]);
        return new SimpleIdentity($row[UserRepository::COLUMN_ID], $row[UserRepository::COLUMN_ROLE], $arr);
    }
}