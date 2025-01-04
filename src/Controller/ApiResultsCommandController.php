<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\User;
use App\Repository\ResultRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Utility\Utils;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

use function in_array;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 */
#[Route(
    path: ApiResultsQueryInterface::RUTA_API,
    name: 'api_results_'
)]
class ApiResultsCommandController extends AbstractController implements ApiResultsCommandInterface
{
    private const ROLE_ADMIN = 'ROLE_ADMIN';
    private const HEADER_ETAG = 'ETag';

    public function __construct(
        private readonly ResultRepositoryInterface $resultRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * @param Request $request
     * @param int $resultId
     * @return Response
     * @see ApiResultsCommandInterface::deleteAction()
     */
    #[Route(
        path: "/{resultId}.{_format}",
        name: 'delete',
        requirements: [
            'resultId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null ],
        methods: [Request::METHOD_DELETE],
    )]
    public function deleteAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        $errorIfNoAuthorized = $this->getErrorIfNoAuthorized($format);
        if($errorIfNoAuthorized !== null) {
            return $errorIfNoAuthorized;
        }

        $resultOrResponse = $this->getResultOrBuildErrorResponse($format,$resultId);
        if($resultOrResponse instanceof Response){
            return $resultOrResponse;
        }

        $this->resultRepository->remove($resultOrResponse);
        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     * @see ApiResultsCommandInterface::postAction()
     *
     */
    #[Route(
        path: ".{_format}",
        name: 'post',
        requirements: [
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null ],
        methods: [Request::METHOD_POST],
    )]
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        $errorIfNoAuthorized = $this->getErrorIfNoAuthorized($format);
        if($errorIfNoAuthorized !== null) {
            return $errorIfNoAuthorized;
        }

        $postData = json_decode((string) $request->getContent(), true,
            512, JSON_THROW_ON_ERROR);
        $userOrResponse = $this->checkValidationsBeforeSave(null, $postData, $format);
        if($userOrResponse instanceof Response){
            return $userOrResponse;
        }

        $result = $this->captureData($postData, $userOrResponse);
        $this->resultRepository->save($result);

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ Result::RESULT_ATTR => $result ],
            $format
            ,[
                'Location' => $request->getScheme() . '://' . $request->getHttpHost() .
                    ApiResultsQueryInterface::RUTA_API . '/' . $result->getId(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $resultId
     * @return Response
     * @throws \JsonException
     * @see ApiResultsCommandInterface::putAction()
     */
    #[Route(
        path: "/{resultId}.{_format}",
        name: 'put',
        requirements: [
            'resultId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null, 'resultId' => 0 ],
        methods: [Request::METHOD_PUT],
    )]
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        $errorIfNoAuthorized = $this->getErrorIfNoAuthorized($format);
        if($errorIfNoAuthorized !== null) {
            return $errorIfNoAuthorized;
        }
        $resultOrResponse = $this->getResultOrBuildErrorResponse($format,$resultId);
        if($resultOrResponse instanceof Response){
            return $resultOrResponse;
        }

        $postData = json_decode((string) $request->getContent(), true,
            512, JSON_THROW_ON_ERROR);
        $userOrResponse = $this->checkValidationsBeforeSave($resultOrResponse, $postData, $format);
        if($userOrResponse instanceof Response){
            return $userOrResponse;
        }

        $etag = md5(json_encode($resultOrResponse, JSON_THROW_ON_ERROR));
        if (!$request->headers->has('If-Match') || $etag != $request->headers->get('If-Match')) {
            return Utils::errorMessage(
                Response::HTTP_PRECONDITION_FAILED,
                'PRECONDITION FAILED: one or more conditions given evaluated to false',
                $format
            ); // 412
        }

        $result = $this->captureData($postData, $userOrResponse, $resultOrResponse);
        $this->resultRepository->save($result);
        return Utils::apiResponse(
            209,                        // 209 - Content Returned
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                self::HEADER_ETAG=>$etag
            ]
        );
    }


    /**
     * @param ?Result                 $result
     * @param array<string, mixed> $postData
     * @param string               $format
     * @return Response|User
     */
    private function checkValidationsBeforeSave(?Result $result, array $postData, string $format) : Response|User {
        if (($error = $this->checkInputData($result !== null, $postData, $format)) !== null)
            return $error;

        $userIdentifier = $result ? $postData[Result::USER_ATTR]
            : ($postData[Result::USER_ATTR] ?? $this->getUser()->getUserIdentifier());

        if (($error = $this->validateUserForbidden($userIdentifier, $format)) !== null)
            return $error;

        if (!$user = $this->userRepository->findByEmail($userIdentifier))
            return Utils::buildResponseNotFound($format, "User $userIdentifier not found in db");

        return $user;
    }

    /**
     * @param bool                 $edit
     * @param array<string, mixed> $postData
     * @param string               $format
     * @return Response|null
     */
    private function checkInputData(bool $edit, array $postData, string $format):?Response{
        $errors = [];
        if($edit) {
            if(!isset($postData[Result::USER_ATTR]))
                $errors[] = "User (email) is required.";
        }
        if (!isset($postData[Result::RESULT_ATTR])) $errors[] = "Result score is required.";
        else {
            $newResult = $postData[Result::RESULT_ATTR];
            if (!is_int($newResult)) $errors[] = "Result score must be an integer.";
            else {
                $rVal = (int)$newResult;
                if ($rVal < 0)  $errors[] = "Result score must be >= 0.";
            }
        }
        if (isset($postData[Result::TIME_ATTR])) {
            $newTimestamp = $postData[Result::TIME_ATTR];
            $time = DateTime::createFromFormat('Y-m-d H:i:s', $newTimestamp);
            if (!$time)
                $errors[] = "Invalid time format. Use 'YYYY-MM-DD HH:MM:SS'.";
        }
        if (!empty($errors)) {
            $messageValitations = "Some request field does not have the correct format:";
            foreach ($errors as $err) $messageValitations .= $err . " | ";

            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, $messageValitations, $format);
        }
        return null;
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
    private function getResultOrBuildErrorResponse(string $format, int $resultId):Response|Result {
        $result = $this->resultRepository->findById($resultId);
        if(!$result) return Utils::buildResponseNotFound($format,"Result with id #$resultId not found");
        if (($error = $this->validateUserForbidden($result->getUser()->getUserIdentifier(), $format)) !== null)
            return $error;
        return $result;
    }

    /**
     * @param string $userIdentifier
     * @param string               $format
     * @return Response|null
     */
    private function validateUserForbidden(string $userIdentifier, string $format):?Response{
        $currentUser = $this->getUser()->getUserIdentifier();
        if(!$this->isGranted(self::ROLE_ADMIN) && strcasecmp($userIdentifier, $currentUser) !== 0 ){
            return Utils::buildResponseForbidden($format);
        }
        return null;
    }

    /**
     * @param array<string, mixed>  $postData
     * @param User                  $user
     * @param Result|null           $result
     * @return Result
     */
    private function captureData(array $postData, User $user,
                                 ?Result $result = new Result()) : Result {
        $result ->setResult($postData[Result::RESULT_ATTR]);
        $time = DateTime::createFromFormat('Y-m-d H:i:s',$postData[Result::TIME_ATTR]??'');
        if(!$time) $time = new DateTime('now');
        $result ->setTime($time);
        $result ->setUser($user);
        return $result;
    }
}
