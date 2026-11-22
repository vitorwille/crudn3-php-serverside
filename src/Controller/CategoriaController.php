<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Categoria;
use App\Entity\Componente;
use App\Http\Request;
use App\Http\Response;
use App\Support\HttpException;
use App\Support\Validate;

final class CategoriaController extends BaseController
{
    public function index(Request $request): Response
    {
        $categorias = $this->em->getRepository(Categoria::class)->findBy([], ['nome' => 'ASC']);

        return Response::json([
            'total' => count($categorias),
            'dados' => array_map(
                static fn (Categoria $categoria): array => $categoria->toArray(),
                $categorias,
            ),
        ]);
    }

    public function show(Request $request): Response
    {
        $categoria = $this->findCategoria((int) $request->params['id']);

        return Response::json(['dados' => $categoria->toArray(true)]);
    }

    public function store(Request $request): Response
    {
        $nome = Validate::requiredString($request->body, 'nome_categoria', 'nome_categoria', 100);
        $this->assertNomeDisponivel($nome);

        $categoria = new Categoria($nome);

        $this->em->persist($categoria);
        $this->em->flush();

        return Response::json([
            'mensagem' => 'Categoria criada com sucesso.',
            'dados' => $categoria->toArray(),
        ], 201);
    }

    public function update(Request $request): Response
    {
        $categoria = $this->findCategoria((int) $request->params['id']);

        if ($request->has('nome_categoria')) {
            $nome = Validate::requiredString($request->body, 'nome_categoria', 'nome_categoria', 100);
            $this->assertNomeDisponivel($nome, $categoria->getId());
            $categoria->setNome($nome);
        }

        $this->em->flush();

        return Response::json([
            'mensagem' => 'Categoria atualizada com sucesso.',
            'dados' => $categoria->toArray(true),
        ]);
    }

    public function destroy(Request $request): Response
    {
        $categoria = $this->findCategoria((int) $request->params['id']);

        if ($categoria->getComponentes()->count() > 0) {
            throw new HttpException(
                409,
                'Não é possível excluir a categoria enquanto existirem componentes vinculados a ela.',
                ['total_componentes' => $categoria->getComponentes()->count()],
            );
        }

        $this->em->remove($categoria);
        $this->em->flush();

        return Response::json(['mensagem' => 'Categoria excluída com sucesso.']);
    }

    public function componentes(Request $request): Response
    {
        $categoria = $this->findCategoria((int) $request->params['id']);

        /** @var \App\Repository\ComponenteRepository $repository */
        $repository = $this->em->getRepository(Componente::class);
        $componentes = $repository->search($categoria->getId());

        return Response::json([
            'categoria' => $categoria->toArray(),
            'total' => count($componentes),
            'dados' => array_map(
                static fn (Componente $componente): array => $componente->toArray(),
                $componentes,
            ),
        ]);
    }

    private function assertNomeDisponivel(string $nome, ?int $ignorarId = null): void
    {
        $existente = $this->em->getRepository(Categoria::class)->findOneBy(['nome' => $nome]);

        if ($existente !== null && $existente->getId() !== $ignorarId) {
            throw new HttpException(409, 'Já existe uma categoria com este nome.', [
                'id_categoria' => $existente->getId(),
                'nome_categoria' => $existente->getNome(),
            ]);
        }
    }
}
