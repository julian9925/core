<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
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
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A generic DataResponse class that is used to return generic data responses
 * for responders to transform
 */
class DataResponse extends Response {

	/**
	 * response data
	 * @var array|object
	 */
	protected $data;


	/**
	 * @param array|object $data the object or array that should be transformed
	 * @param int $statusCode the Http status code, defaults to 200
	 * @param array $headers additional key value based headers
	 */
	public function __construct($data=array(), $statusCode=Http::STATUS_OK,
	                            array $headers=array()) {
		$this->data = $data;
		$this->setStatus($statusCode);
		$this->setHeaders(array_merge($this->getHeaders(), $headers));
	}


	/**
	 * Sets values in the data json array
	 * @param array|object $data an array or object which will be transformed
	 * @return DataResponse Reference to this object
	 */
	public function setData($data){
		$this->data = $data;

		return $this;
	}


	/**
	 * Used to get the set parameters
	 * @return array the data
	 */
	public function getData(){
		return $this->data;
	}


}
