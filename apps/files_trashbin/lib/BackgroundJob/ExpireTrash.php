<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\BackgroundJob;

use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Trashbin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCP\IUserManager;

class ExpireTrash extends TimedJob {
	private IConfig $config;
	private Expiration $expiration;
	private IUserManager $userManager;

	public function __construct(
		IConfig $config,
		IUserManager $userManager,
		Expiration $expiration,
		ITimeFactory $time
	) {
		parent::__construct($time);
		// Run once per 30 minutes
		$this->setInterval(60 * 30);

		$this->config = $config;
		$this->userManager = $userManager;
		$this->expiration = $expiration;
	}

	protected function run($argument) {
		$backgroundJob = $this->appConfig->getValueString('files_trashbin', 'background_job_expire_trash', 'yes');
		if ($backgroundJob === 'no') {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$this->userManager->callForSeenUsers(function (IUser $user) {
			$uid = $user->getUID();
			if (!$this->setupFS($uid)) {
				continue;
			}
			$dirContent = Helper::getTrashFiles('/', $uid, 'mtime');
			Trashbin::deleteExpiredFiles($dirContent, $uid);

			$offset++;

			if ($stopTime < time()) {
				$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', $offset);
				\OC_Util::tearDownFS();
				return;
			}
		}

		$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		\OC_Util::tearDownFS();
	}

	/**
	 * Act on behalf on trash item owner
	 */
	protected function setupFS(string $user): bool {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);

		// Check if this user has a trashbin directory
		$view = new \OC\Files\View('/' . $user);
		if (!$view->is_dir('/files_trashbin/files')) {
			return false;
		}

		return true;
	}
}
