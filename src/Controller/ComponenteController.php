<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Componente;
use App\Http\Request;
use App\Http\Response;
use App\Support\HttpException;
use App\Support\Validate;

final class ComponenteController extends BaseController
{
    public function index(Request $request): Response
    {
        $categoriaId = $this->queryInt($request, ['categoria', 'categoria_id', 'id_categoria'], 'categoria');
        $equipamentoId = $this->queryInt($request, ['equipamento', 'equipamento_id', 'id_equipamento'], 'equipamento');

        if ($categoriaId !== null) {
            $this->findCategoria($categoriaId);
        }

        if ($equipamentoId !== null) {
            $this->findEquipamento($equipamentoId);
        }

        /** @var \App\Repository\ComponenteRepository $repository */
        $repository = $this->em->getRepository(Componente::class);
        $componentes = $repository->search($categoriaId, $equipamentoId);

        return Response::json([
            'filtros' => [
                'id_categoria' => $categoriaId,
                'id_equipamento' => $equipamentoId,
            ],
            'total' => count($componentes),
            'dados' => array_map(
                static fn (Componente $componente): array => $componente->toArray(),
                $componentes,
            ),
        ]);
    }

    public function show(Request $request): Response
    {
        $componente = $this->findComponente((int) $request->params['id']);

        return Response::json(['dados' => $componente->toArray()]);
    }

    public function store(Request $request): Response
    {
        $nome = Validate::requiredString($request->body, 'nome_componente', 'nome_componente', 150);
        $descricao = Validate::optionalString($request->body, 'desc_componente', 'desc_componente');
        $categoriaId = Validate::int($request->body, 'id_categoria', 'id_categoria');
        $categoria = $this->findCategoria($categoriaId);

        $componente = new Componente($nome, $categoria, $descricao);

        $this->em->persist($componente);
        $this->em->flush();

        return Response::json([
            'mensagem' => 'Componente criado com sucesso.',
            'dados' => $componente->toArray(),
        ], 201);
    }

    public function update(Request $request): Response
    {
        $componente = $this->findComponente((int) $request->params['id']);

        if ($request->has('nome_componente')) {
            $componente->setNome(
                Validate::requiredString($request->body, 'nome_componente', 'nome_componente', 150),
            );
        }

        if ($request->has('desc_componente')) {
            $componente->setDescricao(
                Validate::optionalString($request->body, 'desc_componente', 'desc_componente'),
            );
        }

        if ($request->has('id_categoria')) {
            $categoriaId = Validate::int($request->body, 'id_categoria', 'id_categoria');
            $componente->setCategoria($this->findCategoria($categoriaId));
        }

        $this->em->flush();

        return Response::json([
            'mensagem' => 'Componente atualizado com sucesso.',
            'dados' => $componente->toArray(),
        ]);
    }

    public function destroy(Request $request): Response
    {
        $componente = $this->findComponente((int) $request->params['id']);

        if ($componente->getEquipamentos()->count() > 0) {
            throw new HttpException(
                409,
                'Não é possível excluir o componente enquanto ele estiver vinculado a um ou mais equipamentos.',
                ['total_equipamentos' => $componente->getEquipamentos()->count()],
            );
        }

        $this->em->remove($componente);
        $this->em->flush();

        return Response::json(['mensagem' => 'Componente excluído com sucesso.']);
    }
}
