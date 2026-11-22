<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'categoria')]
class Categoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_categoria', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nome_categoria', type: 'string', length: 100, unique: true)]
    private string $nome;

    /** @var Collection<int, Componente> */
    #[ORM\OneToMany(targetEntity: Componente::class, mappedBy: 'categoria')]
    private Collection $componentes;

    public function __construct(string $nome = '')
    {
        $this->nome = $nome;
        $this->componentes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): self
    {
        $this->nome = $nome;

        return $this;
    }

    /**
     * @return Collection<int, Componente>
     */
    public function getComponentes(): Collection
    {
        return $this->componentes;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $withComponentes = false): array
    {
        $data = [
            'id_categoria' => $this->id,
            'nome_categoria' => $this->nome,
        ];

        if ($withComponentes) {
            $componentes = $this->componentes->toArray();

            usort(
                $componentes,
                static fn (Componente $a, Componente $b): int => strcasecmp($a->getNome(), $b->getNome()),
            );

            $data['total_componentes'] = count($componentes);
            $data['componentes'] = array_map(
                static fn (Componente $componente): array => $componente->toArray(false),
                $componentes,
            );
        }

        return $data;
    }
}
