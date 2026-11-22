<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'usuario')]
class Usuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_usuario', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nome_usuario', type: 'string', length: 60, unique: true)]
    private string $nomeUsuario;

    #[ORM\Column(name: 'email', type: 'string', length: 150, unique: true)]
    private string $email;

    #[ORM\Column(name: 'senha', type: 'string', length: 255)]
    private string $senha;

    #[ORM\Column(name: 'criado_em', type: 'datetime_immutable')]
    private \DateTimeImmutable $criadoEm;

    public function __construct(string $nomeUsuario = '', string $email = '', string $senha = '')
    {
        $this->nomeUsuario = $nomeUsuario;
        $this->email = $email;
        $this->senha = $senha;
        $this->criadoEm = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomeUsuario(): string
    {
        return $this->nomeUsuario;
    }

    public function setNomeUsuario(string $nomeUsuario): self
    {
        $this->nomeUsuario = $nomeUsuario;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSenha(): string
    {
        return $this->senha;
    }

    public function setSenha(string $senha): self
    {
        $this->senha = $senha;

        return $this;
    }

    public function setSenhaHash(string $senhaHash): self
    {
        $this->senha = $senhaHash;

        return $this;
    }

    public function verificarSenha(string $senha): bool
    {
        return password_verify($senha, $this->senha);
    }

    public function getCriadoEm(): \DateTimeImmutable
    {
        return $this->criadoEm;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_usuario' => $this->id,
            'nome_usuario' => $this->nomeUsuario,
            'email' => $this->email,
            'criado_em' => $this->criadoEm->format(DATE_ATOM),
        ];
    }
}
