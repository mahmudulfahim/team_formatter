<?php

namespace App\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
	description: 'Team hierarchy structure',
	required: ['teamName', 'parentTeam', 'managerName']
)]
class Team
{
	#[OA\Property(example: 'Sales')]
	public string $teamName;

	#[OA\Property(example: 'Business team')]
	public string $parentTeam;

	#[OA\Property(example: 'Steph Stephans')]
	public string $managerName;

	#[OA\Property(example: 'Business Unit')]
	public string $businessUnit;

	#[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Team'))]
	public array $teams = [];
}
