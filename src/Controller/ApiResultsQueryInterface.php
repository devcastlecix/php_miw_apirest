<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Interface ApiResultsQueryInterface
 *
 * @package App\Controller
 */
interface ApiResultsQueryInterface
{
    public final const RUTA_API = '/api/v1/results';

    /**
     * **CGET** Action
     *
     * Summary: Retrieves the collection of Result resources.
     * _Notes_: Returns all results (if ROLE_ADMIN) or the user’s results (if ROLE_USER).
     *
     * @param Request $request The HTTP request
     *
     * @return Response The HTTP response containing the results collection
     */
    public function cgetAction(Request $request): Response;

    /**
     * **GET** Action
     *
     * Summary: Retrieves a Result resource based on a single ID.
     * _Notes_: Returns the result identified by `resultId` if the current user has access.
     *
     * @param Request $request The HTTP request
     * @param int     $resultId The ID of the Result
     *
     * @return Response The HTTP response containing the single Result resource
     */
    public function getAction(Request $request, int $resultId): Response;

    /**
     * **OPTIONS** Action
     *
     * Summary: Provides the list of HTTP supported methods
     * _Notes_: Return a `Allow` header with a list of HTTP supported methods for:
     *          - The collection (/results) if `$resultId` is null
     *          - The single resource (/results/{resultId}) otherwise
     *
     * @param int|null $resultId The Result ID (or null for the collection)
     *
     * @return Response The HTTP response with the `Allow` header
     */
    public function optionsAction(?int $resultId): Response;

    /**
     * **OPTIONS** Action
     *
     * Summary: Provides the list of HTTP supported methods
     * _Notes_: Return a `Allow` header with a list of HTTP supported methods for:
     *          - The collection (/results.{_format}/{sort?id}/{order?asc}) is with sorting
     *
     * @return Response The HTTP response with the `Allow` header
     */
    public function optionsCGetSortingAction(): Response;

}
