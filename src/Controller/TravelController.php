<?php

namespace App\Controller;

use App\Repository\TravelRepository;
use App\Repository\UserRepository;
use App\Services\RequestService;
use App\Services\ResponseService;
use App\Services\TravelService;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TravelController extends HelloworldController
{
    // ------------------------ >

    public function __construct(
        ResponseService $responseService,
        RequestService $requestService,
        ValidatorInterface $validator,
        private NormalizerInterface $normalizer,
        private TravelService $travelService,
        private TravelRepository $travelRepository,
        private UserRepository $userRepository,
    ) {
        parent::__construct($responseService, $requestService, $validator, $normalizer);
    }

    // ------------------------ >

    /**
     * @Route("/travels", name="get_travel", methods={ "GET" })
     *
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function index(): Response
    {
        $loggedUser = $this->getLoggedUser($this->userRepository);
        $travel = $this->travelRepository->findAll();

        return $this->buildSuccessResponse(Response::HTTP_OK, $travel, $loggedUser);
    }

    /**
     * @Route("/travels", name="create_travel", methods={ "POST" })
     *
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function addAction(Request $request): Response
    {
        $parameters = $this->getContent($request);
        $loggedUser = $this->getLoggedUser($this->userRepository);

        // No logged user
        if (null === $loggedUser) {
            return $this->responseService->error403('auth.unauthorized', 'Vous n\'êtes pas autorisé à effectué cette action');
        }

        $errors = $this->validate($parameters, [
            'name' => [new Type(['type' => 'string']), new NotBlank()],
            'budget' => [new Type(['type' => 'float']), new NotBlank()],
            'description' => [new Optional([new Type(['type' => 'string']), new NotBlank()])],
            'startedAt' => [new Optional([new DateTime(['format' => 'Y-m-d']), new NotBlank()])],
            'endedAt' => [new Optional([new DateTime(['format' => 'Y-m-d']), new NotBlank()])],
            'isSharable' => [new Type(['type' => 'bool']), new NotBlank()],
        ]);

        if (!empty($errors)) {
            return $errors;
        }

        $startedAt = $this->getDate($request, $request->request->get('startedAt'));
        $endedAt = $this->getDate($request, $request->request->get('endedAt'));

        $travel = $this->travelService->create(
            $loggedUser,
            $parameters['name'],
            $parameters['budget'],
            $startedAt,
            $endedAt,
            $parameters['description'],
            $parameters['isSharable']
        );

        $this->normalizer->normalize($travel, null, ['groups' => 'travel:read']);

        return $this->buildSuccessResponse(Response::HTTP_CREATED, $travel, $loggedUser);
    }
}
