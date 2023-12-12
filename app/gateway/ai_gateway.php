<?php

namespace Gateway;

use Config\DatabaseCon;
use Config\Connection;
use Error;
use PDOException;
use PDO;
use PDORow;

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

	public function addModel(string $user_uuid, string $category, string $model)
	{
		$res_exists = $this->checkIfCategoryExists($user_uuid, $category, $model);

		if ($res_exists === 1) {
			$code = $this->insertModel($user_uuid, $category, $model);
		} else if ($res_exists === 0) {
			$code = $this->updateModel($user_uuid, $category, $model);
		} else {
			return -1;
		}

		if ($code === -1) return -1;
		return 0;
	}

	public function insertModel(string $user_uuid, string $category, string $model)
	{
		error_log("INSERT SQL");
		error_log($user_uuid);
		error_log($category);
		error_log($model);
		$query = "INSERT INTO trained_model VALUES(null, :user_uuid, :category, :model);";

		try {
			$this->con->executeQuery($query, array(
				':user_uuid' => array($user_uuid, PDO::PARAM_STR),
				':category' => array($category, PDO::PARAM_STR),
				':model' => array($model, PDO::PARAM_STR)
			));
		} catch (PDOException) {
			return -1;
		}
		return 0;
	}

	public function updateModel(string $user_uuid, string $category, string $model)
	{
		$query = "UPDATE trained_model SET model = :model WHERE user_id = :user_uuid and category = :category;";

		try {
			$this->con->executeQuery($query, array(
				':user_uuid' => array($user_uuid, PDO::PARAM_STR),
				':category' => array($category, PDO::PARAM_STR),
				':model' => array($model, PDO::PARAM_STR)
			));
		} catch (PDOException) {
			return -1;
		}

		return 0;
	}

	public function checkIfCategoryExists(string $user_uuid, string $category)
	{
		$query = "SELECT category FROM trained_model WHERE category = :category and user_id = :user_uuid;";
		error_log("CHECK SQL");
		error_log($user_uuid);
		error_log($category);
		try {
			error_log("AAAAAH");
			$this->con->executeQuery($query, array(
				':user_uuid' => array($user_uuid, PDO::PARAM_STR),
				':category' => array($category, PDO::PARAM_STR)
			));
			error_log("BBBBBH");
			$results = $this->con->getResults();
		} catch (PDOException) {
			return -1;
		}

		error_log("AFTER CHECK SQL");

		if (count($results) === 0) {
			return 1;
		}

		return 0;
	}
}
