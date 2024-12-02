<?php

declare(strict_types=1);

namespace App\Responses;

use Nette;
use Nette\Application\Response;

final class BadRequestResponse implements Response
{
    use Nette\SmartObject;

    public function __construct(
        private string $reason
    )
    {}

    function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setContentType('application/json', 'utf-8');
        $httpResponse->setCode(Nette\Http\IResponse::S400_BAD_REQUEST);
        echo Nette\Utils\Json::encode(['message' => $this->reason]);
    }
}