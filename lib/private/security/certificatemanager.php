<?php
/**
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Security;

use OC\Files\Filesystem;
use OCP\ICertificateManager;

/**
 * Manage trusted certificates for users
 */
class CertificateManager implements ICertificateManager {
	/**
	 * @var string
	 */
	protected $uid;

	/**
	 * @var \OC\Files\View
	 */
	protected $view;

	/**
	 * @param string $uid
	 * @param \OC\Files\View $view relative zu data/
	 */
	public function __construct($uid, \OC\Files\View $view) {
		$this->uid = $uid;
		$this->view = $view;
	}

	/**
	 * Returns all certificates trusted by the user
	 *
	 * @return \OCP\ICertificate[]
	 */
	public function listCertificates() {
		$path = $this->getPathToCertificates() . 'uploads/';
		if (!$this->view->is_dir($path)) {
			return array();
		}
		$result = array();
		$handle = $this->view->opendir($path);
		if (!is_resource($handle)) {
			return array();
		}
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				try {
					$result[] = new Certificate($this->view->file_get_contents($path . $file), $file);
				} catch(\Exception $e) {}
			}
		}
		closedir($handle);
		return $result;
	}

	/**
	 * create the certificate bundle of all trusted certificated
	 */
	protected function createCertificateBundle() {
		$path = $this->getPathToCertificates();
		$certs = $this->listCertificates();

		$fh_certs = $this->view->fopen($path . '/rootcerts.crt', 'w');
		foreach ($certs as $cert) {
			$file = $path . '/uploads/' . $cert->getName();
			$data = $this->view->file_get_contents($file);
			if (strpos($data, 'BEGIN CERTIFICATE')) {
				fwrite($fh_certs, $data);
				fwrite($fh_certs, "\r\n");
			}
		}

		fclose($fh_certs);
	}

	/**
	 * Save the certificate and re-generate the certificate bundle
	 *
	 * @param string $certificate the certificate data
	 * @param string $name the filename for the certificate
	 * @return \OCP\ICertificate|void|bool
	 * @throws \Exception If the certificate could not get added
	 */
	public function addCertificate($certificate, $name) {
		if (!Filesystem::isValidPath($name) or Filesystem::isFileBlacklisted($name)) {
			return false;
		}

		$dir = $this->getPathToCertificates() . 'uploads/';
		if (!$this->view->file_exists($dir)) {
			$this->view->mkdir($dir);
		}

		try {
			$file = $dir . $name;
			$certificateObject = new Certificate($certificate, $name);
			$this->view->file_put_contents($file, $certificate);
			$this->createCertificateBundle();
			return $certificateObject;
		} catch (\Exception $e) {
			throw $e;
		}

	}

	/**
	 * Remove the certificate and re-generate the certificate bundle
	 *
	 * @param string $name
	 * @return bool
	 */
	public function removeCertificate($name) {
		if (!Filesystem::isValidPath($name)) {
			return false;
		}
		$path = $this->getPathToCertificates() . 'uploads/';
		if ($this->view->file_exists($path . $name)) {
			$this->view->unlink($path . $name);
			$this->createCertificateBundle();
		}
		return true;
	}

	/**
	 * Get the path to the certificate bundle for this user
	 *
	 * @return string
	 */
	public function getCertificateBundle() {
		return $this->getPathToCertificates() . 'rootcerts.crt';
	}

	private function getPathToCertificates() {
		$path = is_null($this->uid) ? '/files_external/' : '/' . $this->uid . '/files_external/';

		return $path;
	}
}
