<?php

namespace App\Service;

use RuntimeException;

class TeamHierarchyService
{
	/**
	 * Builds a tree based on parent_team
	 *
	 * @param array $data
	 * @return array
	 */
	public function buildTeamHierarchy(array $data): array {
		$teams = array_column($data, 'team');
		$parentTeams = array_column($data, 'parent_team');
		$rootParent = array_diff($parentTeams, $teams)[0] ?? '';

		$rootTeams = array_filter($data, fn($t) => $t['parent_team'] === $rootParent);

		if (empty($rootTeams)) {
			throw new RuntimeException('No root team found in hierarchy');
		}

		$hierarchy = [];
		foreach ($rootTeams as $rootTeam) {
			$teamName = $rootTeam['team'];
			$hierarchy[$teamName] = $this->buildTeamNode($teamName, $data);
		}

		return $hierarchy;
	}

	/**
	 * Builds a node based on return json properties structure
	 *
	 * @param string $teamName
	 * @param array $allTeams
	 * @return array
	 */
	private function buildTeamNode(string $teamName, array $allTeams): array {
		$node = [
			'teamName' => '',
			'parentTeam' => '',
			'managerName' => '',
			'businessUnit' => '',
			'teams' => []
		];

		foreach ($allTeams as $teamData) {
			if ($teamData['team'] === $teamName) {
				$node = [
					'teamName' => $teamData['team'],
					'parentTeam' => $teamData['parent_team'],
					'managerName' => $teamData['manager_name'],
					'businessUnit' => $teamData['business_unit'] ?? '',
					'teams' => []
				];
				break;
			}
		}

		$children = array_filter($allTeams, fn($t) => $t['parent_team'] === $teamName);

		foreach ($children as $childTeam) {
			$childTeamName = $childTeam['team'];
			$node['teams'][$childTeamName] = $this->buildTeamNode($childTeamName, $allTeams);
		}

		return $node;
	}

	/**
	 * Returns the hierarchy from root down to the specified team
	 * If team not found, returns the original hierarchy
	 *
	 * @param array $hierarchy
	 * @param string $query
	 * @return array|array[]
	 */
	public function filterByTeam(array $hierarchy, string $query): array {
		$result = [];

		foreach ($hierarchy as $currentTeamName => $teamData) {
			if ($currentTeamName === $query) {
				$result[$currentTeamName] = $teamData;
				continue;
			}

			$filteredChildren = $this->filterChildrenForTeam($teamData['teams'], $query);

			if (!empty($filteredChildren)) {
				$result[$currentTeamName] = [
					'teamName' => $teamData['teamName'],
					'parentTeam' => $teamData['parentTeam'],
					'managerName' => $teamData['managerName'],
					'businessUnit' => $teamData['businessUnit'],
					'teams' => $filteredChildren
				];
			}
		}

		return $result ?: $hierarchy;
	}

	/**
	 * @param array $teams
	 * @param string $targetTeamName
	 * @return array
	 */
	private function filterChildrenForTeam(array $teams, string $targetTeamName): array {
		$result = [];

		foreach ($teams as $teamName => $teamData) {
			if ($teamName === $targetTeamName) {
				$result[$teamName] = $teamData;
				continue;
			}

			$childResults = $this->filterChildrenForTeam($teamData['teams'], $targetTeamName);

			if (!empty($childResults)) {
				$result[$teamName] = [
					'teamName' => $teamData['teamName'],
					'parentTeam' => $teamData['parentTeam'],
					'managerName' => $teamData['managerName'],
					'businessUnit' => $teamData['businessUnit'],
					'teams' => $childResults
				];
			}
		}

		return $result;
	}
}