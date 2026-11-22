<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Usuario;
use App\Http\Request;
use App\Http\Response;
use App\Support\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;

final class AuthController extends BaseController
{
    public function __construct(
        EntityManagerInterface $em,
        private readonly string $jwtSecret,
        private readonly int $jwtTtl,
    ) {
        parent::__construct($em);
    }

    public function login(Request $request): Response
    {
        $login = $request->input('usuario', $request->input('login', $request->input('email')));
        $senha = $request->input('senha');

        if (!is_string($login) || trim($login) === '') {
            throw new HttpException(422, 'O campo usuário é obrigatório.', ['campo' => 'usuario']);
        }

        if (!is_string($senha) || $senha === '') {
            throw new HttpException(422, 'O campo senha é obrigatório.', ['campo' => 'senha']);
        }

        $usuario = $this
            ->em
            ->createQueryBuilder()
            ->select('u')
            ->from(Usuario::class, 'u')
            ->where('u.nomeUsuario = :login OR u.email = :login')
            ->setParameter('login', trim($login))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$usuario instanceof Usuario || !$usuario->verificarSenha($senha)) {
            throw new HttpException(401, 'Usuário ou senha inválidos.');
        }

        $agora = time();
        $expiraEm = $agora + $this->jwtTtl;

        $token = JWT::encode([
            'sub' => (string) $usuario->getId(),
            'nome_usuario' => $usuario->getNomeUsuario(),
            'email' => $usuario->getEmail(),
            'iat' => $agora,
            'exp' => $expiraEm,
        ], $this->jwtSecret, 'HS256');

        return Response::json([
            'mensagem' => 'Login realizado com sucesso.',
            'token' => $token,
            'tipo' => 'Bearer',
            'expira_em' => gmdate(DATE_ATOM, $expiraEm),
            'usuario' => $usuario->toArray(),
        ]);
    }

    public function me(Request $request): Response
    {
        $id = $request->user['sub'] ?? null;

        if (!is_numeric($id)) {
            throw new HttpException(401, 'Token inválido.');
        }

        $usuario = $this->em->getRepository(Usuario::class)->find((int) $id);

        if ($usuario === null) {
            throw new HttpException(401, 'O usuário do token informado é inválido.');
        }

        return Response::json(['dados' => $usuario->toArray()]);
    }
}
