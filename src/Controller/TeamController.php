<?php

namespace App\Controller;

use App\Service\CsvReaderService;
use App\Service\TeamDataValidatorService;
use App\Service\TeamHierarchyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TeamController extends AbstractController
{
	public function __construct(
		private readonly CsvReaderService $csvReaderService,
		private readonly TeamDataValidatorService $teamDataValidatorService,
		private readonly TeamHierarchyService $teamHierarchyGeneratorService,
	)
	{
	}

	/**
	 * API endpoint /api/format-team
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	#[Route('/api/format-team', name: 'format_team', methods: ['POST'])]
	public function formatTeam(Request $request): JsonResponse
	{
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
