<?php

namespace App\Tests\Controller;

use App\Entity\Result;
use App\Entity\User;
use Faker\Factory as FakerFactoryAlias;
use Generator;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsQueryController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';
    /**
     * @var array<string,string>
     */
    private array $roleUserHeaders;
    /**
     * @var array<string,string>
     */
    private array $roleAdminHeaders;

    protected function setUp(): void
    {
        $this->roleUserHeaders = self::getTokenHeaders(self::$role_user[User::EMAIL_ATTR],
            self::$role_user[User::PASSWD_ATTR]);
        $this->roleAdminHeaders = self::getTokenHeaders(self::$role_admin[User::EMAIL_ATTR],
            self::$role_admin[User::PASSWD_ATTR]);
    }

    public function testOptionsResultAction204NoContent(): void
    {
        // OPTIONS /api/v1/results -> GET,POST,OPTIONS
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertNotEmpty($response->headers->get('Allow'));
        $options = 'GET,POST,OPTIONS';
        self::assertEquals($options,$response->headers->get('Allow'));

        // OPTIONS /api/v1/results/{id} -> GET,PUT,DELETE,OPTIONS
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertNotEmpty($response->headers->get('Allow'));
        $options = 'GET,PUT,DELETE,OPTIONS';
        self::assertEquals($options,$response->headers->get('Allow'));

        // OPTIONS /api/v1/results/{id} -> GET,PUT,DELETE,OPTIONS
        self::$client->request(Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
        self::assertEquals($options,$response->headers->get('Allow'));

        // OPTIONS /api/v1/results.json/time/desc -> GET,OPTIONS
        $path = self::RUTA_API . '.json/time/desc';
        self::$client->request(Request::METHOD_OPTIONS, $path);
        $response = self::$client->getResponse();
        $options = 'GET,OPTIONS';
        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertNotEmpty($response->headers->get('Allow'));
        $this->assertEquals($options, $response->headers->get('Allow'));
    }

    /**
     * Test GET    /results 401 UNAUTHORIZED
     * Test GET    /results.json 401 UNAUTHORIZED
     * Test GET    /results.xml 401 UNAUTHORIZED
     * Test POST   /results 401 UNAUTHORIZED
     * Test POST   /results.json 401 UNAUTHORIZED
     * Test POST   /results.xml 401 UNAUTHORIZED
     * Test GET    /results/{resultId} 401 UNAUTHORIZED
     * Test GET    /results/{resultId}.json 401 UNAUTHORIZED
     * Test GET    /results/{resultId}.xml 401 UNAUTHORIZED
     * Test PUT    /results/{resultId} 401 UNAUTHORIZED
     * Test PUT    /results/{resultId}.json 401 UNAUTHORIZED
     * Test PUT    /results/{resultId}.xml 401 UNAUTHORIZED
     * Test DELETE /results/{resultId} 401 UNAUTHORIZED
     * Test DELETE /results/{resultId}.json 401 UNAUTHORIZED
     * Test DELETE /results/{resultId}.xml 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @param string|null $format
     * @dataProvider providerRoutes401
     * @return void
     */
    public function testResultStatus401Unauthorized(string $method, string $uri,
                                                    ?string $format): void
    {
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            [ 'HTTP_ACCEPT' => 'application/json' ]
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_UNAUTHORIZED,
            $format
        );
    }

    /**
     * Test GET    /results         404 NOT_FOUND
     * Test GET    /results.json    404 NOT_FOUND
     * Test GET    /results.xml     404 NOT_FOUND
     * @dataProvider providerRoutesFormat
     * @return void
     */
    public function testCGetResultStatus404NotFound(string $uri, ?string $format):void{
        $token = self::getTokenHeaders(self::$role_user2[User::EMAIL_ATTR],self::$role_user2[User::PASSWD_ATTR]);
        self::$client->request(
            Request::METHOD_GET,
            $uri,
            [],
            [],
            $token
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,Response::HTTP_NOT_FOUND, $format);
    }

    /**
     * Test GET    /results/{resultId}         404 NOT_FOUND
     * Test GET    /results/{resultId}.json    404 NOT_FOUND
     * Test GET    /results/{resultId}.xml     404 NOT_FOUND
     * Test PUT    /results/{resultId}         404 NOT_FOUND
     * Test PUT    /results/{resultId}.json    404 NOT_FOUND
     * Test PUT    /results/{resultId}.xml     404 NOT_FOUND
     * Test DELETE /results/{resultId}         404 NOT_FOUND
     * Test DELETE /results/{resultId}.json    404 NOT_FOUND
     * Test DELETE /results/{resultId}.xml     404 NOT_FOUND
     * @param string $method
     * @param string|null $format
     * @param int $resultId result id. returned by testDeleteResultAction204NoContent()
     * @dataProvider providerRoutes404
     * @return void
     * @depends      testDeleteResultAction204NoContent
     */
    public function testResultStatus404NotFound(string $method,  ?string $format, int $resultId): void{
        $pdata = [
            Result::RESULT_ATTR=> 10,
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR]
        ];
        self::$client->request(
            $method,
            $uri = self::RUTA_API. '/' . $resultId.(!$format ? '' : '.'.$format),
            [],
            [],
            $this->roleUserHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,Response::HTTP_NOT_FOUND, $format);
    }

    /**
     * Test POST    /results         403 FORBIDDEN
     * Test POST    /results.json    403 FORBIDDEN
     * Test POST    /results.xml     403 FORBIDDEN
     * @param string $uri
     * @param string|null $format
     * @dataProvider providerRoutesFormat
     * @return void
     */
    public function testPostResultStatus403Forbidden(string $uri, ?string $format): void{
        $pdata = [
            Result::RESULT_ATTR=> 10,
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR]
        ];
        self::$client->request(
            Request::METHOD_POST,
            $uri,
            [],
            [],
            $this->roleUserHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,Response::HTTP_FORBIDDEN, $format);
    }

    /**
     * Test POST    /results         404 FORBIDDEN
     * Test POST    /results.json    403 FORBIDDEN
     * Test POST    /results.xml     403 FORBIDDEN
     * @param string $uri
     * @param string|null $format
     * @dataProvider providerRoutesFormat
     * @return void
     */
    public function testPostResultStatus422NotFoundUser(string $uri, ?string $format): void{
        $pdata = [
            Result::RESULT_ATTR=> 10,
            Result::USER_ATTR=> 'notfound@miw.upm.es'
        ];
        self::$client->request(
            Request::METHOD_POST,
            $uri,
            [],
            [],
            $this->roleAdminHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,Response::HTTP_NOT_FOUND, $format);
    }

    /**
     * Test POST    /results         422 UNPROCESSABLE_ENTITY
     * Test POST    /results.json    422 UNPROCESSABLE_ENTITY
     * Test POST    /results.xml     422 UNPROCESSABLE_ENTITY
     * @param mixed $result
     * @param string|null $time
     * @param string $uri
     * @param string|null $format
     * @dataProvider providerRoutesPost422
     * @return void
     */
    public function testPostResultStatus422UnprocessableEntity(mixed $result, ?string $time,
                                                               string $uri, ?string $format): void{
        $pdata = [
            Result::RESULT_ATTR=> $result,
            Result::TIME_ATTR=> $time,
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR]
        ];
        self::$client->request(
            Request::METHOD_POST,
            $uri,
            [],
            [],
            $this->roleAdminHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,Response::HTTP_UNPROCESSABLE_ENTITY, $format);
    }

    /**
     * Test POST /results 201 Created
     *
     * @return array<string,string> result data
     */
    public function testPostResultAction201CreatedRoleAdmin(): array
    {
        return $this->executePostResultAction201Created(10, null, null,
            self::$role_admin[User::EMAIL_ATTR],  $this->roleAdminHeaders);
    }

    /**
     * Test POST /results 201 Created
     *
     * @return array<string,string> result data
     */
    public function testPostResultAction201CreatedRoleUser(): array
    {
        return $this->executePostResultAction201Created(self::$faker->numberBetween(10, 50), null, null,
            self::$role_user[User::EMAIL_ATTR],  $this->roleUserHeaders);
    }

    /**
     * execute post status 200
     * @param int $resultValue
     * @param string|null $time
     * @param string|null $email
     * @param string $emailLogin
     * @param array<string, string> $rolHeaders
     *
     * @return array<string,string>
     */
    private function executePostResultAction201Created(int $resultValue, ?string $time, ?string $email,
                                                       string $emailLogin, array $rolHeaders): array
    {
        $p_data = [
            Result::RESULT_ATTR=> $resultValue,
            Result::TIME_ATTR=> $time,
            Result::USER_ATTR=>$email
        ];

        // 201
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $rolHeaders,
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->headers->get('Location'));
        self::assertJson(strval($response->getContent()));
        $result = json_decode(strval($response->getContent()), true)[Result::RESULT_ATTR];
        $user = $result[User::USER_ATTR];
        self::assertNotEmpty($result['id']);
        self::assertNotEmpty($result['time']);
        self::assertSame($resultValue, $result[Result::RESULT_ATTR]);
        self::assertSame($emailLogin, $user[User::EMAIL_ATTR]);

        return $result;
    }


    /**
     * Test GET /result 200 Ok
     *
     * @depends testPostResultAction201CreatedRoleAdmin
     *
     * @return string ETag header
     */
    public function testCGetResultAction200OkUserAdmin(): string
    {
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], $this->roleAdminHeaders);
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        $r_body = strval($response->getContent());
        self::assertJson($r_body);
        $users = json_decode($r_body, true);
        self::assertArrayHasKey('results', $users);

        return (string) $response->getEtag();
    }

    /**
     * @param string $etag Etag received from other test
     * @return void
     * @depends testCGetResultAction200OkUserAdmin
     */
    public function testCGetResultsStatus304NotModified(string $etag): void{
        $headers = array_merge($this->roleAdminHeaders,['HTTP_If-None-Match'=>[$etag]]);
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test GET /results/{resultId} 200 OK
     * @param array<string,string> $result result returned by testPostResultAction201CreatedRoleAdmin()
     * @return string Entity Tag
     * @depends testPostResultAction201CreatedRoleAdmin
     */
    public function testGetResultAction200OkRolAdmin(array $result): string {
        $id = $result['id'];
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API.'/'.$id,
            [],
            [],
            $this->roleAdminHeaders
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_OK,$response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertJson(strval($response->getContent()));
        $resultResponse = json_decode(strval($response->getContent()),true);
        self::assertNotEmpty($resultResponse);
        $etag = (string) $response->getEtag();
        self::assertNotEmpty($etag);
        return $etag;
    }

    /**
     * Test GET /result/{resultId} 304 NOT MODIFIED
     *
     * @param array<string,string> $result result returned by testPostResultAction201CreatedRoleAdmin()
     * @param string $etag returned by testPostResultAction201CreatedRoleAdmin
     * @return string Entity Tag
     *
     * @depends testPostResultAction201CreatedRoleAdmin
     * @depends testGetResultAction200OkRolAdmin
     */
    public function testGetResultAction304NotModified(array $result, string $etag): string
    {
        $headers = array_merge(
            $this->roleAdminHeaders,
            [ 'HTTP_If-None-Match' => [$etag] ]
        );
        self::$client->request(Request::METHOD_GET, self::RUTA_API . '/' . $result['id'], [], [], $headers);
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());

        return $etag;
    }

    /**
     * Test GET    /results/{resultId}    403 FORBIDDEN
     * Test PUT    /results/{resultId}    403 FORBIDDEN
     * Test DELETE /results/{resultId}    403 FORBIDDEN
     * @param string $method
     * @param string|null $format
     * @param array<string, string> $result result returned by testPostResultAction201CreatedRoleAdmin()
     * @dataProvider providerRoutes403
     * @depends testPostResultAction201CreatedRoleAdmin
     * @return void
     */
    public function testResultStatus403Forbidden(string $method, ?string $format, array $result): void{
        $pdata = [
            Result::RESULT_ATTR=> self::$faker->numberBetween(10, 50),
            Result::USER_ATTR=>self::$role_user[User::EMAIL_ATTR]
        ];
        $uri = self::RUTA_API. '/' . $result['id'].(!$format ? '' : '.'.$format);
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            $this->roleUserHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,Response::HTTP_FORBIDDEN, $format);
    }

    /**
     * Test PUT /result/{resultId} 209 Content Returned
     *
     * @param   array<string,string> $result result returned by testPostResultAction201CreatedRoleAdmin()
     * @param   string $etag returned by testGetResultAction304NotModified()
     * @return  array<string,string> modified result data
     * @depends testPostResultAction201CreatedRoleAdmin
     * @depends testGetResultAction304NotModified
     * @depends testCGetResultsStatus304NotModified
     */
    public function testPutResultAction209ContentReturned(array $result, string $etag): array
    {
        $p_data = [
            Result::RESULT_ATTR=> self::$faker->numberBetween(10, 50),
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR]
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            array_merge(
                $this->roleAdminHeaders,
                [ 'HTTP_If-Match' => $etag ]
            ),
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();

        self::assertSame(209, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $result_aux = json_decode($r_body, true)[Result::RESULT_ATTR];
        $user = $result_aux[User::USER_ATTR];
        self::assertSame($result['id'], $result_aux['id']);
        self::assertSame($p_data[Result::RESULT_ATTR], $result_aux[Result::RESULT_ATTR]);
        self::assertSame($p_data[User::USER_ATTR], $user[User::EMAIL_ATTR]);

        return $result_aux;
    }

    /**
     * Test PUT /results/{resultId} 412 PRECONDITION_FAILED
     *
     * @param   array<string,string> $result result returned by testPutResultAction209ContentReturned()
     * @dataProvider providerFormat
     * @depends testPutResultAction209ContentReturned
     * @return  void
     */
    public function testPutResultAction412PreconditionFailed(?string $format, array $result): void
    {
        $p_data = [
            Result::RESULT_ATTR=> self::$faker->numberBetween(10, 50),
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR]
        ];
        $uri = self::RUTA_API. '/' . $result['id'].(!$format ? '' : '.'.$format);
        self::$client->request(
            Request::METHOD_PUT,
            $uri,
            [],
            [],
            $this->roleAdminHeaders,
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_PRECONDITION_FAILED, $format);
    }

    /**
     * Test PUT /results/{resultId} 422 Unprocessable Entity
     *
     * @param   array<string,string> $result result returned by testPutResultAction209ContentReturned()
     * @dataProvider providerFormat
     * @depends testPutResultAction209ContentReturned
     * @return  void
     */
    public function testPutResultAction422UnprocessableEntity(?string $format, array $result): void
    {
        $p_data = [
            Result::RESULT_ATTR=> self::$faker->numberBetween(10, 50)
        ];
        $uri = self::RUTA_API. '/' . $result['id'].(!$format ? '' : '.'.$format);
        self::$client->request(
            Request::METHOD_PUT,
            $uri,
            [],
            [],
            $this->roleAdminHeaders,
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_UNPROCESSABLE_ENTITY, $format);
    }

    /**
     * Test DELETE /results/{resultId} 204 No Content
     *
     * @param   array<string,string> $result result returned by testPutResultAction209ContentReturned()
     * @return  int resultId
     * @depends testPutResultAction209ContentReturned
     * @depends testCGetResultAction200OkUserAdmin
     * @depends testResultStatus403Forbidden
     * @depends testPutResultAction422UnprocessableEntity
     * @depends testPutResultAction412PreconditionFailed
     */
    public function testDeleteResultAction204NoContent(array $result): int
    {
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            $this->roleAdminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertEmpty($response->getContent());

        return intval($result['id']);
    }

    /**
     * * * * * * * * * *
     * P R O V I D E R S
     * * * * * * * * * *
     */

    /**
     * Route provider (expected status: 403 FORBIDDEN)
     *
     * @return Generator name => [ method, url, format ]
     */
    #[ArrayShape([
        'getAction403' => "array",
        'getAction403Json' => "array",
        'getAction403Xml' => "array",
        'putAction403' => "array",
        'putAction403Json' => "array",
        'putAction403Xml' => "array",
        'deleteAction403' => "array",
        'deleteAction403Json' => "array",
        'deleteAction403Xml' => "array"
    ])]

    public function providerRoutes403(): Generator
    {
        yield 'getAction403'    => [ Request::METHOD_GET, null ];
        yield 'getAction403Json'    => [ Request::METHOD_GET, 'json' ];
        yield 'getAction403Xml'    => [ Request::METHOD_GET, 'xml' ];
        yield 'putAction403'    => [ Request::METHOD_PUT, null ];
        yield 'putAction403Json'    => [ Request::METHOD_PUT, 'json' ];
        yield 'putAction403Xml'    => [ Request::METHOD_PUT, 'xml' ];
        yield 'deleteAction403' => [ Request::METHOD_DELETE, null];
        yield 'deleteAction403Json' => [ Request::METHOD_DELETE, 'json' ];
        yield 'deleteAction403Xml' => [ Request::METHOD_DELETE, 'xml' ];
    }

    /**
     * Route provider for post ok rol_user (expected status: 201 Created)
     * @return Generator name => [ result, time, email ]
     */
    #[ArrayShape([
        'result_minimal' => "array",
        'result_no_email' => "array",
        'result_no_time' => "array",
        'result_all_fields' => "array"
    ])]
    public function providerRoutesPost201RoleUser(): Generator
    {
        $fakerAux = FakerFactoryAlias::create('es_ES');
        yield 'result_minimal'   => [ $fakerAux->numberBetween(10, 50), null, null , self::RUTA_API, null];
        yield 'result_no_email'   => [ $fakerAux->numberBetween(10, 50), '2025-01-04 11:12:09', null, self::RUTA_API, null ];
        yield 'result_no_time'   => [ $fakerAux->numberBetween(10, 50), null, $_ENV['ROLE_USER_EMAIL'], self::RUTA_API, null ];
        yield 'result_all_fields'   => [ $fakerAux->numberBetween(10, 50), '2025-01-05 11:12:10', $_ENV['ROLE_USER_EMAIL'], self::RUTA_API, null  ];
    }

    /**
     * Route provider for post validations input (expected status: 422 UNPROCESSABLE_ENTITY)
     * @return Generator name => [  ]
     */
    #[ArrayShape([
        'no_result' => "array",
        'no_result_json' => "array",
        'no_result_xml' => "array",
        'result_no_integer' => "array",
        'result_no_integer_json' => "array",
        'result_no_integer_xml' => "array",
        'result_negative' => "array",
        'result_negative_json' => "array",
        'result_negative_xml' => "array",
        'no_format_time' => "array",
        'no_format_time_json' => "array",
        'no_format_time_xml' => "array"
    ])]
    public function providerRoutesPost422(): Generator
    {
        $fakerAux = FakerFactoryAlias::create('es_ES');
        yield 'no_result'   => [ null, null, self::RUTA_API, null ];
        yield 'no_result_json'   => [ null, null, self::RUTA_API.'.json', 'json' ];
        yield 'no_result_xml'   => [ null, null, self::RUTA_API.'.xml', 'xml' ];
        yield 'result_no_integer'   => [ $fakerAux->randomLetter(), null, self::RUTA_API, null ];
        yield 'result_no_integer_json'   => [ $fakerAux->randomLetter(), null, self::RUTA_API.'.json', 'json' ];
        yield 'result_no_integer_xml'   => [ $fakerAux->randomLetter(), null, self::RUTA_API.'.xml', 'xml'];
        yield 'result_negative'   => [ $fakerAux->numberBetween(-999, -1), null, self::RUTA_API, null ];
        yield 'result_negative_json'   => [  $fakerAux->numberBetween(-999, -1), null, self::RUTA_API.'.json', 'json' ];
        yield 'result_negative_xml'   => [  $fakerAux->numberBetween(-999, -1), null, self::RUTA_API.'.xml', 'xml' ];
        yield 'no_format_time'   => [ $fakerAux->numberBetween(10, 50), $fakerAux->randomLetter(), self::RUTA_API, null ];
        yield 'no_format_time_json'   => [ $fakerAux->numberBetween(10, 50), $fakerAux->randomLetter(), self::RUTA_API.'.json', 'json' ];
        yield 'no_format_time_xml'   => [ $fakerAux->numberBetween(10, 50), $fakerAux->randomLetter(), self::RUTA_API.'.xml', 'xml' ];
    }

    /**
     * Route provider for put
     *   - other result (expected status: 412 PRECONDITION_FAILED)
     * @return Generator name => [ format ]
     */
    #[ArrayShape([
        'withoutFormat' => "array",
        'formatJson' => "array",
        'formatXml' => "array",
    ])]
    public function providerFormat(): Generator
    {
        yield 'withoutFormat'   => [  null ];
        yield 'formatJson'   => [ 'json' ];
        yield 'formatXml'   => [ 'xml' ];
    }

    /**
     * Route provider for post
     *   - other user (expected status: 403 FORBIDDEN)
     *   - not found user (expected status: 404 NOT_FOUND)
     * @return Generator name => [ url, format ]
     */
    #[ArrayShape([
        'withoutFormat' => "array",
        'formatJson' => "array",
        'formatXml' => "array",
    ])]
    public function providerRoutesFormat(): Generator
    {
        yield 'withoutFormat'   => [ self::RUTA_API, null ];
        yield 'formatJson'   => [ self::RUTA_API . '.json', 'json' ];
        yield 'formatXml'   => [ self::RUTA_API . '.xml', 'xml' ];
    }

    /**
     * Route provider (expected status: 404 NOT_FOUND)
     *
     * @return Generator name => [ method, url, format ]
     */
    #[ArrayShape([
        'getAction404' => "array",
        'getAction404Json' => "array",
        'getAction404Xml' => "array",
        'putAction404' => "array",
        'putAction404Json' => "array",
        'putAction404Xml' => "array",
        'deleteAction404' => "array",
        'deleteAction404Json' => "array",
        'deleteAction404Xml' => "array",
    ])]
    public function providerRoutes404(): Generator
    {
        yield 'getAction404'   => [ Request::METHOD_GET,   null ];
        yield 'getAction404Json'   => [ Request::METHOD_GET,   'json' ];
        yield 'getAction404Xml'   => [ Request::METHOD_GET, 'xml' ];
        yield 'putAction404'    => [ Request::METHOD_PUT,  null ];
        yield 'putAction404Json'    => [ Request::METHOD_PUT,  'json' ];
        yield 'putAction404Xml'    => [ Request::METHOD_PUT, 'xml' ];
        yield 'deleteAction404'    => [ Request::METHOD_DELETE, null ];
        yield 'deleteAction404Json'    => [ Request::METHOD_DELETE, 'json' ];
        yield 'deleteAction404Xml'    => [ Request::METHOD_DELETE,  'xml' ];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return Generator name => [ method, url, format ]
     */
    #[ArrayShape([
        'cgetAction401' => "array",
        'cgetAction401Json' => "array",
        'cgetAction401Xml' => "array",
        'getAction401' => "array",
        'getAction401Json' => "array",
        'getAction401Xml' => "array",
        'postAction401' => "array",
        'postAction401Json' => "array",
        'postAction401Xml' => "array",
        'putAction401' => "array",
        'putAction401Json' => "array",
        'putAction401Xml' => "array",
        'deleteAction401' => "array",
        'deleteAction401Json' => "array",
        'deleteAction401Xml' => "array"
    ])]
    public function providerRoutes401(): Generator
    {
        yield 'cgetAction401'   => [ Request::METHOD_GET,    self::RUTA_API , null];
        yield 'cgetAction401Json'   => [ Request::METHOD_GET,    self::RUTA_API.'.json', 'json' ];
        yield 'cgetAction401Xml'   => [ Request::METHOD_GET,    self::RUTA_API.'.xml', 'xml' ];
        yield 'getAction401'    => [ Request::METHOD_GET,    self::RUTA_API . '/1', null ];
        yield 'getAction401Json'    => [ Request::METHOD_GET,    self::RUTA_API . '/1.json', 'json' ];
        yield 'getAction401Xml'    => [ Request::METHOD_GET,    self::RUTA_API . '/1.xml', 'xml' ];
        yield 'postAction401'   => [ Request::METHOD_POST,   self::RUTA_API, null ];
        yield 'postAction401Json'   => [ Request::METHOD_POST,   self::RUTA_API.'.json', 'json'];
        yield 'postAction401Xml'   => [ Request::METHOD_POST,   self::RUTA_API.'.xml', 'xml' ];
        yield 'putAction401'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1', null ];
        yield 'putAction401Json'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1.json', 'json' ];
        yield 'putAction401Xml'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1.xml', 'xml' ];
        yield 'deleteAction401' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' , null];
        yield 'deleteAction401Json' => [ Request::METHOD_DELETE, self::RUTA_API . '/1.json', 'json' ];
        yield 'deleteAction401Xml' => [ Request::METHOD_DELETE, self::RUTA_API . '/1.xml', 'xml' ];
    }
}