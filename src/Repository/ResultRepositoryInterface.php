<?php

namespace App\Repository;

use App\Entity\Result;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interface ResultRepositoryInterface
 *
 * @package App\Repository
 */
interface ResultRepositoryInterface
{
    /**
     * Encuentra todos los “Result” sin filtrar,
     * con un posible criterio de ordenación por campo.
     *
     * @param string|null $sort Campo por el que se ordena (p. ej. 'id', 'time', etc.)
     * @param string      $order 'ASC' o 'DESC'
     *
     * @return Result[]
     */
    public function findAllSorted(?string $sort, string $order = 'ASC'): array;

    /**
     * Encuentra todos los “Result” de un usuario concreto (ROLE_USER),
     * con posible criterio de ordenación.
     *
     * @param UserInterface        $user
     * @param string|null          $sort
     * @param string               $order
     *
     * @return Result[]
     */
    public function findByUserSorted(UserInterface $user, ?string $sort, string $order = 'ASC'): array;

    /**
     * Encuentra el “Result” en base de su indentificador,
     *
     * @param int        $resultId
     *
     * @return Result|null
     */
    public function findById(int $resultId): Result|null;

    /**
     * Guarda el nuevo objeto “Result”
     * segun sea la operación insertar o modificar
     * @param Result        $result
     *
     * @return void
     */
    public function save(Result $result):void;

    /**
     * Elimina el objeto “Result”
     * @param Result        $result
     *
     * @return void
     */
    public function remove(Result $result): void;
}
