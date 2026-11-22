<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Componente;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Componente>
 */
final class ComponenteRepository extends EntityRepository
{
    /**
     * consulta componentes filtrando por categoria e/ou equipamento.
     *
     * @return list<Componente>
     */
    public function search(?int $categoriaId = null, ?int $equipamentoId = null): array
    {
        $qb = $this
            ->createQueryBuilder('c')
            ->addSelect('categoria')
            ->join('c.categoria', 'categoria')
            ->orderBy('c.nome', 'ASC');

        if ($categoriaId !== null) {
            $qb
                ->andWhere('categoria.id = :categoriaId')
                ->setParameter('categoriaId', $categoriaId);
        }

        if ($equipamentoId !== null) {
            $qb
                ->join('c.equipamentos', 'equipamento')
                ->andWhere('equipamento.id = :equipamentoId')
                ->setParameter('equipamentoId', $equipamentoId);
        }

        /** @var list<Componente> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }
}
