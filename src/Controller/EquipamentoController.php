<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Componente;
use App\Entity\Equipamento;
use App\Http\Request;
use App\Http\Response;
use App\Support\HttpException;
use App\Support\Validate;

use function Symfony\Component\String\u;

final class EquipamentoController extends BaseController
{
    public function index(Request $request): Response
    {
        $equipamentos = $this->em->getRepository(Equipamento::class)->findBy([], ['nome' => 'ASC']);

        return Response::json([
            'total' => count($equipamentos),
            'dados' => array_map(
                static fn(Equipamento $equipamento): array => $equipamento->toArray(),
                $equipamentos,
            ),
        ]);
    }

    public function show(Request $request): Response
    {
        $equipamento = $this->findEquipamento((int) $request->params['id']);

        return Response::json(['dados' => $equipamento->toArray()]);
    }

    public function store(Request $request): Response
    {
        $nome = Validate::requiredString($request->body, 'nome_equipamento', 'nome_equipamento', 150);
        $componentes = $this->resolveComponentes($this->componentIds($request));

        $this->assertCategoriasObrigatorias($componentes);

        $equipamento = new Equipamento($nome);
        $equipamento->replaceComponentes($componentes);

        $this->em->persist($equipamento);
        $this->em->flush();

        return Response::json([
            'mensagem' => 'Equipamento criado com sucesso.',
            'dados' => $equipamento->toArray(),
        ], 201);
    }

    public function update(Request $request): Response
    {
        $equipamento = $this->findEquipamento((int) $request->params['id']);

        if ($request->has('nome_equipamento')) {
            $equipamento->setNome(
                Validate::requiredString($request->body, 'nome_equipamento', 'nome_equipamento', 150),
            );
        }

        if ($request->has('componentes') || $request->has('componentes_ids')) {
            $componentes = $this->resolveComponentes($this->componentIds($request));
            $this->assertCategoriasObrigatorias($componentes);
            $equipamento->replaceComponentes($componentes);
        } else {
            $this->assertCategoriasObrigatorias($equipamento->getComponentes());
        }

        $this->em->flush();

        return Response::json([
            'mensagem' => 'Equipamento atualizado com sucesso.',
            'dados' => $equipamento->toArray(),
        ]);
    }

    public function destroy(Request $request): Response
    {
        $equipamento = $this->findEquipamento((int) $request->params['id']);

        $this->em->remove($equipamento);
        $this->em->flush();

        return Response::json(['mensagem' => 'Equipamento excluído com sucesso.']);
    }

    public function componentes(Request $request): Response
    {
        $equipamento = $this->findEquipamento((int) $request->params['id']);
        $categoriaId = $this->queryInt($request, ['categoria', 'categoria_id', 'id_categoria'], 'categoria');

        if ($categoriaId !== null) {
            $this->findCategoria($categoriaId);
        }

        /** @var \App\Repository\ComponenteRepository $repository */
        $repository = $this->em->getRepository(Componente::class);
        $componentes = $repository->search($categoriaId, $equipamento->getId());

        return Response::json([
            'equipamento' => [
                'id_equipamento' => $equipamento->getId(),
                'nome_equipamento' => $equipamento->getNome(),
            ],
            'filtros' => ['id_categoria' => $categoriaId],
            'total' => count($componentes),
            'dados' => array_map(
                static fn(Componente $componente): array => $componente->toArray(),
                $componentes,
            ),
        ]);
    }

    /**
     * @return list<int>
     */
    private function componentIds(Request $request): array
    {
        $key = $request->has('componentes') || !$request->has('componentes_ids')
            ? 'componentes'
            : 'componentes_ids';

        return Validate::componentIdList($request->body, $key, $key);
    }

    /**
     * @param list<int> $ids
     * @return list<Componente>
     */
    private function resolveComponentes(array $ids): array
    {
        $repository = $this->em->getRepository(Componente::class);
        $componentes = [];
        $naoEncontrados = [];

        foreach ($ids as $id) {
            $componente = $repository->find($id);

            if ($componente === null) {
                $naoEncontrados[] = $id;
                continue;
            }

            $componentes[] = $componente;
        }

        if ($naoEncontrados !== []) {
            throw new HttpException(422, 'Um ou mais componentes informados não existem.', [
                'codigos_componentes_nao_encontrados' => $naoEncontrados,
            ]);
        }

        return $componentes;
    }

    /**
     * rn: todo equipamento deve ter, no mínimo, um componente de armazenamento e um de processamento.
     *
     * @param iterable<Componente> $componentes
     */
    private function assertCategoriasObrigatorias(iterable $componentes): void
    {
        $possuiArmazenamento = false;
        $possuiProcessamento = false;
        $categorias = [];

        foreach ($componentes as $componente) {
            $nomeCategoria = $componente->getCategoria()?->getNome() ?? '';
            $categoriaNormalizada = u($nomeCategoria)->lower()->ascii()->toString();

            if ($nomeCategoria !== '') {
                $categorias[$nomeCategoria] = true;
            }

            if (str_contains($categoriaNormalizada, 'armazen')) {
                $possuiArmazenamento = true;
            }

            if (str_contains($categoriaNormalizada, 'process')) {
                $possuiProcessamento = true;
            }
        }

        if (!$possuiArmazenamento || !$possuiProcessamento) {
            throw new HttpException(
                422,
                'O equipamento deve possuir pelo menos um componente da categoria de armazenamento e um da categoria de processamento.',
                [
                    'categorias_informadas' => array_keys($categorias),
                    'regras' => [
                        'pelo_menos_um_componente_de_armazenamento',
                        'pelo_menos_um_componente_de_processamento',
                    ],
                ],
            );
        }
    }
}
