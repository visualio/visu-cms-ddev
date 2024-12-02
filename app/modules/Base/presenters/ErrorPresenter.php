<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\Responses;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\BadRequestException;
use Nette\Application\Helpers;
use Nette\Http;
use Tracy\ILogger;


final class ErrorPresenter implements Nette\Application\IPresenter
{
	use Nette\SmartObject;

	public function __construct(
        private ILogger $logger
    )
	{}


	public function run(Request $request): Response
	{
		$exception = $request->getParameter('exception');

		// 404
		if ($exception instanceof BadRequestException) {
			$presenterName = $request->getParameter('request') ? $request->getParameter('request')->getPresenterName() : $request->getPresenterName();
            [$module, $action] = Helpers::splitName($presenterName);
			$module = $module ? $module : 'Front';
			$errorPresenter = $module . ':Error4xx';
			return new Responses\ForwardResponse($request->setPresenterName($errorPresenter));
		}
		

		// 500
		$this->logger->log($exception, ILogger::EXCEPTION);
		return new Responses\CallbackResponse(function (Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type'))) {
				require __DIR__ . '/templates/Error/500.phtml';
			}
		});
	}

}
