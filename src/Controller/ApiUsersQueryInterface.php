<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Interface ApiUsersQueryInterface
 *
 * @package App\Controller
 *
 */
interface ApiUsersQueryInterface
{
    public final const RUTA_API = '/api/v1/users';

    /**
     * **CGET** Action<br>
     * Summary: Retrieves the collection of User resources.<br>
     * _Notes_: Returns all users from the system that the user has access to.
     */
    public function cgetAction(Request $request): Response;

    /**
     * **GET** Action<br>
     * Summary: Retrieves a User resource based on a single ID.<br>
     * _Notes_: Returns the user identified by <code>userId</code>.
     *
     * @param int $userId User id
     */
    public function getAction(Request $request, int $userId): Response;

    /**
     * **CGET** Action<br>
     * Summary: Retrieves the collection of Results resources.<br>
     * _Notes_: Returns all results from user specified with user id.
     */
    public function cgetActionResults(Request $request, int $userId): Response;

    /**
     * **OPTIONS** Action<br>
     * Summary: Provides the list of HTTP supported methods<br>
     * _Notes_: Return a <code>Allow</code> header with a list of HTTP supported methods.
     *
     * @param  int|null $userId User id
     */
    public function optionsAction(?int $userId): Response;

    /**
     * **OPTIONS** Action<br>
     * Summary: Provides the list of HTTP supported methods<br>
     * _Notes_: Return a <code>Allow</code> header with a list of HTTP supported methods.
     *
     * @param  int $userId User id
     */
    public function optionsActionResults(int $userId): Response;
}
