<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Categoria;
use App\Entity\Componente;
use App\Entity\Equipamento;
use App\Http\Request;
use App\Support\HttpException;
use Doctrine\ORM\EntityManagerInterface;

abstract class BaseController
{
    public function __construct(protected readonly EntityManagerInterface $em)
    {
    }

    protected function findCategoria(int $id): Categoria
    {
        $categoria = $this->em->getRepository(Categoria::class)->find($id);

        if ($categoria === null) {
            throw new HttpException(404, 'Categoria não encontrada.', ['id_categoria' => $id]);
        }

        return $categoria;
    }

    protected function findComponente(int $codigo): Componente
    {
        $componente = $this->em->getRepository(Componente::class)->find($codigo);

        if ($componente === null) {
            throw new HttpException(404, 'Componente não encontrado.', ['codigo_componente' => $codigo]);
        }

        return $componente;
    }

    protected function findEquipamento(int $id): Equipamento
    {
        $equipamento = $this->em->getRepository(Equipamento::class)->find($id);

        if ($equipamento === null) {
            throw new HttpException(404, 'Equipamento não encontrado.', ['id_equipamento' => $id]);
        }

        return $equipamento;
    }

    /**
     * @param list<string> $keys
     */
    protected function queryInt(Request $request, array $keys, string $label): ?int
    {
        foreach ($keys as $key) {
            $value = $request->queryParam($key);

            if ($value === null || $value === '') {
                continue;
            }

            if (!is_string($value) || !ctype_digit($value)) {
                throw new HttpException(422, "O filtro {$label} deve ser um número inteiro positivo.", ['filtro' => $key]);
            }

            return (int) $value;
        }

        return null;
    }
}
