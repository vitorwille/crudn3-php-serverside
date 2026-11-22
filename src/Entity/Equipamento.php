<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'equipamento')]
class Equipamento
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_equipamento', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nome_equipamento', type: 'string', length: 150)]
    private string $nome;

    /** @var Collection<int, Componente> */
    #[ORM\ManyToMany(targetEntity: Componente::class, inversedBy: 'equipamentos')]
    #[ORM\JoinTable(name: 'equipamento_componente')]
    #[ORM\JoinColumn(name: 'id_equipamento', referencedColumnName: 'id_equipamento', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'codigo_componente', referencedColumnName: 'codigo_componente', onDelete: 'CASCADE')]
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

    public function addComponente(Componente $componente): self
    {
        if (!$this->componentes->contains($componente)) {
            $this->componentes->add($componente);
        }

        return $this;
    }

    /**
     * @param list<Componente> $componentes
     */
    public function replaceComponentes(array $componentes): self
    {
        $this->componentes->clear();

        foreach ($componentes as $componente) {
            $this->componentes->add($componente);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $withComponentes = true): array
    {
        $data = [
            'id_equipamento' => $this->id,
            'nome_equipamento' => $this->nome,
        ];

        if ($withComponentes) {
            $componentes = $this->componentes->toArray();

            usort(
                $componentes,
                static fn (Componente $a, Componente $b): int => strcasecmp($a->getNome(), $b->getNome()),
            );

            $data['total_componentes'] = count($componentes);
            $data['componentes'] = array_map(
                static fn (Componente $componente): array => $componente->toArray(),
                $componentes,
            );
        }

        return $data;
    }
}
