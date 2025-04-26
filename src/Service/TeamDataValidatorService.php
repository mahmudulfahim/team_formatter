<?php

namespace App\Service;

class TeamDataValidatorService
{
	/**
	 * Validates data based on rules provided and returns array of validation errors if any
	 *
	 * @param array $data
	 * @return array
	 */
	public function validate(array $data): array
	{
		$errors = [];

		$this->validateStructure($data, $errors);

		if (empty($errors)) {
			$this->validateTeams($data, $errors);

			if (empty($errors)) {
				$this->validateHierarchy($data, $errors);
			}
		}

		return $errors;
	}

	/**
	 * Validates the structure, empty data and required columns
	 *
	 * @param array $csvData
	 * @param array $errors
	 * @return void
	 */
	private function validateStructure(array $csvData, array &$errors): void
	{
		if (empty($csvData)) {
			$errors[] = 'CSV data is empty';
			return;
		}

		$requiredHeaders = ['team', 'parent_team', 'manager_name'];
		foreach ($requiredHeaders as $header) {
			if (!array_key_exists($header, $csvData[0])) {
				$errors[] = sprintf('Missing required column: %s', $header);
			}
		}
	}

	/**
	 * Validate data for each team like team name, manager name and team name duplicates
	 *
	 * @param array $teamsData
	 * @param array $errors
	 * @return void
	 */
	private function validateTeams(array $teamsData, array &$errors): void
	{
		$teamNames = [];
		$row = 1;

		foreach ($teamsData as $team) {
			if (empty($team['team'])) {
				$errors[] = sprintf('Team is required in row %d', $row);
			}

			if (empty($team['manager_name'])) {
				$errors[] = sprintf('Manager name is required in row %d', $row);
			}

			if (isset($teamNames[$team['team']])) {
				$errors[] = sprintf('Duplicate team "%s" found in row %d',
					$team['team'], $team['row']);
			}

			$teamNames[$team['team']] = true;

			$row++;
		}
	}

	/**
	 * Validates hierarchy like only one root node, parent node not missing etc.
	 *
	 * @param array $teamsData
	 * @param array $errors
	 * @return void
	 */
	private function validateHierarchy(array $teamsData, array &$errors): void
	{
		$teams = array_column($teamsData, 'team');
		$parents = array_column($teamsData, 'parent_team');

		$rootNodes = array_diff($parents, $teams);

		if (count($rootNodes) !== 1) {
			$errors[] = 'Hierarchy must have exactly one root node';
			return;
		}

		$rootNode = reset($rootNodes);

		foreach ($teamsData as $team) {
			if ($team['parent_team'] === $rootNode) {
				continue;
			}

			if (!in_array($team['parent_team'], $teams)) {
				$errors[] = sprintf('Parent team "%s" not found for team "%s"',
					$team['parent'], $team['team']);
			}
		}
	}
}