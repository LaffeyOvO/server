<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Async\AsyncManager;
use OC\Async\Exceptions\ProcessAlreadyRunningException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class AsyncProcessController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private AsyncManager $asyncManager,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[PublicPage]
	#[FrontpageRoute(verb: 'POST', url: '/core/asyncProcessFork')]
	public function processFork(string $token): DataResponse {
		$metadata = [];
		try {
			$this->asyncManager->runSession($token, $metadata);
		} catch (ProcessAlreadyRunningException) {
			// TODO: debug() ?
		}

		return new DataResponse(['oui' => $token]);
	}
}
