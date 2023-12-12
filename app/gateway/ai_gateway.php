<?php

namespace Gateway;

use Config\DatabaseCon;
use Config\Connection;
use PDOException;
use PhpParser\Node\Arg;

class AiGateway
{
	private Connection $con;

	public function __construct()
	{
		try {
			$this->con = (new DatabaseCon)->connect();
		} catch (PDOException $e) {
			throw new PDOException($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function getUsersCategoryAndInfo()
	{
		$query = "SELECT u.id, f.category, f.info FROM user u, file f WHERE u.id = f.user_id ORDER BY u.id, f.category DESC;";
		try {
			$this->con->executeQuery($query, array());
			$results = $this->con->getResults();
		} catch (PDOException $e) {
			throw new PDOException($e->getMessage(), $e->getCode(), $e);
		}

		$users = array();
		$tmp_user = null;
		$tmp_category = null;
		$cur_user = null;

		foreach ($results as $row) {
			$user_uuid = $row['id'];

			// Check if user is the same as last user
			if ($user_uuid !== $tmp_user) {
				if ($tmp_user !== null) {
					$users[] = $cur_user;
					$tmp_category = null;
				}
				$tmp_user = $user_uuid;

				$cur_user = array(
					'uuid' => $user_uuid,
					'categories' => array()
				);
			}

			$cur_category = $row['category'];
			$cur_info = $row['info'];

			// Check if category already exist for cur_user
			foreach ($cur_user['categories'] as $category) {
				if (isset($category['name']) && $category['name'] === $cur_category) {
					$tmp_category = true;
					break;
				}
			}

			// Add category if it doesn't exists for cur_user
			if (!$tmp_category) {
				$cur_user['categories'][] = array(
					"name" => $cur_category,
					"infos" => array()
				);
			}

			// Add info to current category
			foreach ($cur_user['categories'] as &$c) {
				if ($c['name'] === $cur_category) {
					$c["infos"][] = array(
						"json" => $cur_info
					);
					break;
				}
			}

			error_log(json_encode($cur_user));
		}

		$users[] = $cur_user;

		$json = json_encode($users, JSON_PRETTY_PRINT);
		return $json;
	}

	public function insertModel(string $user_uuid, string $category, string $model)
	{
		return 0;
	}
}
