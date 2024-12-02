<?php

namespace App\Model;

use App\Model\UserRepository;
use JetBrains\PhpStorm\Pure;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\ArrayHash;
use Nette\Utils\AssertionException;

class User extends DataClass
{
    public ?string $email = null;
    public ?string $role = null;
    public ?string $firstname = null;
    public ?string $lastname = null;

    #[Pure] public function __construct(ActiveRow|ArrayHash $record)
    {
    }
}