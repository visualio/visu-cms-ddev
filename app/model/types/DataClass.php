<?php

namespace App\Model;

use App\Model\Utils;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

abstract class DataClass
{
    public ?int $id = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?int $userId = null;
    public ?int $sort = null;

    public function __construct(ActiveRow|ArrayHash $record)
    {
            $this->id = $record->id;
            $this->dateCreated = $record->date_created;
            $this->dateUpdated = $record->date_updated;
            $this->userId = $record->user_id;
    }
}