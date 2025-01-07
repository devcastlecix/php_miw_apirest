<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\User;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

use function in_array;

/**
 * Class ApiUsersController
 *
 * @package App\Controller
 */
#[Route(
    path: ApiUsersQueryInterface::RUTA_API,
    name: 'api_users_'
)]
class ApiUsersQueryController extends AbstractController implements ApiUsersQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';
    private const ROLE_ADMIN           = "ROLE_ADMIN";

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @see ApiUsersQueryInterface::cgetAction()
     *
     * @throws JsonException
     */
    #[Route(
        path: ".{_format}/{sort?id}",
        name: 'cget',
        requirements: [
            'sort' => "id|email|roles",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json', 'sort' => 'id' ],
        methods: [ Request::METHOD_GET ],
    )]
    public function cgetAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        $errorIfNoAuthorized = $this->getErrorIfNoAuthorized($format);
        if($errorIfNoAuthorized !== null) return $errorIfNoAuthorized;

        $order = strval($request->get('sort'));
        $users = $this->entityManager->getRepository(User::class)->findBy([], [ $order => 'ASC' ]);

        // No hay usuarios?
        // @codeCoverageIgnoreStart
        $etagOrResponse = $this->getResultsOrBuildErrorsOfOperation($request, $format, $users);
        if($etagOrResponse instanceof Response){
            return $etagOrResponse;
        }
        // @codeCoverageIgnoreEnd

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ 'users' => array_map(fn ($u) =>  ['user' => $u], $users) ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etagOrResponse
            ]
        );
    }

    /**
     * @see ApiUsersQueryInterface::getAction()
     *
     * @throws JsonException
     */
    #[Route(
        path: "/{userId}.{_format}",
        name: 'get',
        requirements: [
            "userId" => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json' ],
        methods: [ Request::METHOD_GET ],
    )]
    public function getAction(Request $request, int $userId): Response
    {
        $format = Utils::getFormat($request);
        $errorIfNoAuthorized = $this->getErrorIfNoAuthorized($format);
        if($errorIfNoAuthorized !== null) return $errorIfNoAuthorized;

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class) ->find($userId);

        if (!$user instanceof User) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);    // 404
        }

        // Caching with ETag (password included)
        $etag = md5(json_encode($user, JSON_THROW_ON_ERROR) . $user->getPassword());
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
                return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ User::USER_ATTR => $user ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiUsersQueryInterface::cgetActionResults()
     *
     * @throws JsonException
     */
    #[Route(
        path: "/{userId}/results/{sort?id}",
        name: 'cget_results',
        requirements: [
            "userId" => "\d+",
            //'_format' => "json|xml",
            'sort' => "id|time|result"
        ],
        defaults: [ 'userId' => 0, //'_format' => 'json',
            'sort' => 'id' ],
        methods: [ Request::METHOD_GET ],
    )]
    public function cgetActionResults(Request $request, int $userId): Response
    {
        $format = 'json';
        // @codeCoverageIgnoreStart
        $errorIfNoAuthorized = $this->getErrorIfNoAuthorized($format);
        if($errorIfNoAuthorized !== null) return $errorIfNoAuthorized;

        if(!$this->isGranted(self::ROLE_ADMIN)) return Utils::buildResponseForbidden($format);

        $user = $this->entityManager->getRepository(User::class) ->find($userId);
        if (!$user instanceof User)
            return Utils::buildResponseNotFound($format, "User with id #$userId not found");    // 404

        $order = strval($request->get('sort'));
        $results = $this->entityManager
            ->getRepository(Result::class)
            ->findBy(['user' => $user], [ $order => 'ASC']);
        $etagOrResponse = $this->getResultsOrBuildErrorsOfOperation($request, $format, $results);
        if($etagOrResponse instanceof Response){
            return $etagOrResponse;
        }
        // @codeCoverageIgnoreEnd
        return Utils::apiResponse(
            Response::HTTP_OK,
            [ 'results' => array_map(fn ($u) =>  ['result' => $u], $results) ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etagOrResponse,
            ]
        );
    }

    /**
     * @param Request $request
     * @param string $format
     * @param array<Result|User> $records
     * @return Response|string
     */
    private function getResultsOrBuildErrorsOfOperation(Request $request, string $format,
                                                        array $records):Response|string {
        if (empty($records)) return Utils::buildResponseNotFound($format);    // 404
        $etag = md5((string) json_encode($records, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }
        return $etag;
    }

    /**
     * @see ApiUsersQueryInterface::optionsAction()
     */
     #[Route(
        path: "/{userId}.{_format}",
        name: 'options',
        requirements: [
            'userId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ 'userId' => 0, '_format' => 'json' ],
        methods: [ Request::METHOD_OPTIONS ],
    )]
    public function optionsAction(int|null $userId): Response
    {
        $methods = $userId !== 0
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [ Request::METHOD_GET, Request::METHOD_POST ];
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
     * @see ApiUsersQueryInterface::optionsActionResults()
     */
    #[Route(
        path: "/{userId}/results/{sort?id}",
        name: 'options_results',
        requirements: [
            'userId' => "\d+",
            //'_format' => "json|xml",
            'sort' => "id|time|result"
        ],
        defaults: [ 'userId' => 0,
            //'_format' => 'json',
            'sort' => 'id' ],
        methods: [ Request::METHOD_OPTIONS ],
    )]
    public function optionsActionResults(int $userId): Response
    {
        // @codeCoverageIgnoreStart
        $methods = [ Request::METHOD_GET, Request::METHOD_OPTIONS ];

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(',', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
        // @codeCoverageIgnoreEnd
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

}
