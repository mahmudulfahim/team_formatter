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
	public function buildTeamHierarchy(array $data): array
	{
		$childrenMap = [];

		foreach ($data as $team) {
			$childrenMap[$team['parent_team']][] = $team;
		}

		$rootTeams = array_filter($data, fn($t) => $t['parent_team'] === '');

		if (empty($rootTeams)) {
			throw new RuntimeException('No root team found in hierarchy');
		}

		$hierarchy = [];
		foreach ($rootTeams as $rootTeam) {
			$hierarchy[$rootTeam['team']] = $this->buildTeamNode($rootTeam, $childrenMap);
		}

		return $hierarchy;
	}

	/**
	 * Builds a node based on return json properties structure
	 *
	 * @param array $teamData
	 * @param array $childrenMap
	 * @return array
	 */
	private function buildTeamNode(array $teamData, array $childrenMap): array
	{
		$node = [
			'teamName' => $teamData['team'],
			'parentTeam' => $teamData['parent_team'],
			'managerName' => $teamData['manager_name'],
			'businessUnit' => $teamData['business_unit'] ?? '',
			'teams' => []
		];

		$children = $childrenMap[$teamData['team']] ?? [];

		foreach ($children as $childTeam) {
			$childTeamName = $childTeam['team'];
			$node['teams'][$childTeamName] = $this->buildTeamNode($childTeam, $childrenMap);
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
	public function filterByTeam(array $hierarchy, string $query): array
	{
		$result = $this->filterChildrenForTeam($hierarchy, $query);

		return $result ?: $hierarchy;
	}

	/**
	 * @param array $teams
	 * @param string $targetTeamName
	 * @return array
	 */
	private function filterChildrenForTeam(array $teams, string $targetTeamName): array
	{
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