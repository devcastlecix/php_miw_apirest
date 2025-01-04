<?php

namespace App\Controller;

use App\Entity\Result;
use App\Repository\ResultRepositoryInterface;
use App\Utility\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Exception\JsonException, Request, Response};
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 */
#[Route(
    path: ApiResultsQueryInterface::RUTA_API,
    name: 'api_results_'
)]
class ApiResultsQueryController extends AbstractController implements ApiResultsQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG          = 'ETag';
    private const ROLE_ADMIN           = "ROLE_ADMIN";
    private const HEADER_ALLOW         = 'Allow';

    public function __construct(
        private readonly ResultRepositoryInterface $resultRepository
    ) {
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     * @see ApiResultsQueryInterface::cgetAction()
     */
    #[Route(
        path: ".{_format}/{sort?id}/{order?asc}",
        name: 'cget',
        requirements: [
            'sort' => "id|time|result",
            'order' => "asc|desc",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json', 'sort' => 'id', 'order' => 'asc' ],
        methods: [ Request::METHOD_GET ],
    )]
    public function cgetAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        $errorIfNoAuthorized = $this->getErrorIfNoAuthorized($format);
        if($errorIfNoAuthorized !== null) {
            return $errorIfNoAuthorized;
        }

        $sort = strval($request->get('sort'));
        $order = strtoupper($request->get('order', 'ASC'));

        $results = $this->isGranted(self::ROLE_ADMIN)
            ? $this->resultRepository->findAllSorted($sort, $order)
            : $this->resultRepository->findByUserSorted($this->getUser(), $sort, $order);

        // @codeCoverageIgnoreStart
        if (empty($results)) {
            return Utils::buildResponseNotFound($format);
        }

        $etag = md5((string) json_encode($results, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ 'results' => array_map(fn ($r) =>  ['result' => $r], $results) ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $resultId
     * @return Response
     * @throws \JsonException
     *
     * @see ApiResultsQueryInterface::getAction()
     * */
    #[Route(
        path:"/{resultId}.{_format}",
        name: 'get',
        requirements: [
            "resultId" => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json', "resultId"=>0 ],
        methods: [Request::METHOD_GET],
    )]
    public function getAction(Request $request,int $resultId): Response
    {
        $format = Utils::getFormat($request);
        $errorIfNoAuthorized = $this->getErrorIfNoAuthorized($format);
        if($errorIfNoAuthorized !== null) {
            return $errorIfNoAuthorized;
        }

        $resultOrResponse = $this->getResultOrBuildErrorsOfOperation($format,$resultId);
        if($resultOrResponse instanceof Response){
            return $resultOrResponse;
        }
        $etag = md5(json_encode($resultOrResponse, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }
        return Utils::apiResponse(
            Response::HTTP_OK,
            [ Result::RESULT_ATTR => $resultOrResponse ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @param int|null $resultId
     * @return Response
     * @throws JsonException
     *
     * @see ApiResultsQueryInterface::optionsAction()
     * */
    #[Route(
        path:'/{resultId}.{_format}',
        name: 'options',
        requirements: [
            'resultId'=>'\d+',
            '_format' => "json|xml"
        ],
        defaults: ['resultId'=>0, '_format' => 'json'],
        methods: [ Request::METHOD_OPTIONS ],
    )]
    public function optionsAction(int|null $resultId): Response
    {
        $methods = $resultId && $resultId !== 0
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [Request::METHOD_GET,Request::METHOD_POST];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(',', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
    }

    /**
     * @return Response
     * @throws JsonException
     *
     * @see ApiResultsQueryInterface::optionsCGetSortingAction()
     * */
    #[Route(
        path:".{_format}/{sort?id}/{order?asc}",
        name: 'options_cget_sorting',
        requirements: [
            'sort' => "id|time|result",
            'order' => "asc|desc",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json', 'sort' => 'id', 'order' => 'asc' ],
        methods: [ Request::METHOD_OPTIONS ],
    )]
    public function optionsCGetSortingAction(): Response
    {
        $methods = [Request::METHOD_GET, Request::METHOD_OPTIONS];

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(',', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
    }

    /**
     * @param string               $format
     * @return Response|null
     */
    private function getErrorIfNoAuthorized(string $format): ?Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        return null;
    }

    /**
     * @param string $format
     * @param int $resultId
     * @return Response|Result
     */
    private function getResultOrBuildErrorsOfOperation(string $format, int $resultId):Response|Result {
        $result = $this->resultRepository->findById($resultId);
        if(!$result) return Utils::buildResponseNotFound($format,"Result with id #$resultId not found");

        $userIdentifier = $this->getUser()->getUserIdentifier();
        if(!$this->isGranted(self::ROLE_ADMIN)
            && strcasecmp($userIdentifier, $result->getUser()->getUserIdentifier()) !== 0)
            return Utils::buildResponseForbidden($format);

        return $result;
    }

}
