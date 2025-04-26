<?php

namespace App\Controller;

use App\Service\CsvReaderService;
use App\Service\TeamDataValidatorService;
use App\Service\TeamHierarchyService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use App\Model\Team;

final class TeamController extends AbstractController
{
	public function __construct(
		private readonly CsvReaderService $csvReaderService,
		private readonly TeamDataValidatorService $teamDataValidatorService,
		private readonly TeamHierarchyService $teamHierarchyGeneratorService,
	) {
	}

	/**
	 * API endpoint /api/format-team
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	#[Route('/api/format-team', name: 'format_team', methods: ['POST'])]
	#[OA\Post(
		path: '/api/format-team',
		description: 'Uploads a team data file and returns formatted hierarchy, optionally filtered by team name',
		summary: 'Format team hierarchy from uploaded file',
		requestBody: new OA\RequestBody(
			description: 'Team data file with optional search filter',
			content: new OA\MediaType(
				mediaType: 'multipart/form-data',
				schema: new OA\Schema(
					required: ['file'],
					properties: [
						new OA\Property(property: 'file', description: 'Team data file (CSV/Excel)', type: 'string', format: 'binary'),
						new OA\Property(property: '_q', description: 'Optional team name filter', type: 'string'),
					]
				)
			)
		),
		responses: [
			new OA\Response(
				response: 200,
				description: 'Formatted team hierarchy',
				content: new OA\JsonContent(
					ref: new Model(type: Team::class)
				)
			),
			new OA\Response(
				response: 400,
				description: 'Invalid file format',
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: 'error', type: 'string', example: 'Invalid file format')
					]
				)
			),
			new OA\Response(
				response: 401,
				description: 'Unauthorized',
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: 'error', type: 'string', example: 'Authentication required')
					]
				)
			)
		]
	)]
	#[Security(name: 'Bearer')]
	public function formatTeam(Request $request): JsonResponse {
		$file = $request->files->get('file');
		if ($file == null) {
			return new JsonResponse(
				data: [
					'error' => [
						'message' => 'No file was uploaded.'
					]
				],
				status: Response::HTTP_BAD_REQUEST
			);
		}

		if ($file->getClientOriginalExtension() !== 'csv') {
			return new JsonResponse(
				data: [
					'error' => [
						'message' => 'Please upload csv file.'
					]
				],
				status: Response::HTTP_BAD_REQUEST
			);
		}

		$csvData = $this->csvReaderService->readCsv(file: $file);

		$validationData = $this->teamDataValidatorService->validate(data: $csvData);

		if (!empty($validationData)) {
			return new JsonResponse(
				data: [
					'error' => $validationData
				],
				status: Response::HTTP_BAD_REQUEST
			);
		}

		$teamHierarchy = $this->teamHierarchyGeneratorService->buildTeamHierarchy(data: $csvData);

		$query = $request->get('_q');

		if ($query) {
			$teamHierarchy = $this->teamHierarchyGeneratorService->filterByTeam(hierarchy: $teamHierarchy, query: $query);
		}

		return new JsonResponse(
			data: $teamHierarchy,
			status: Response::HTTP_OK
		);
	}
}
