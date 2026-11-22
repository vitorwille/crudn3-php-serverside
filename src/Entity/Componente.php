<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ComponenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComponenteRepository::class)]
#[ORM\Table(name: 'componente')]
class Componente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'codigo_componente', type: 'integer')]
    private ?int $codigo = null;

    #[ORM\Column(name: 'nome_componente', type: 'string', length: 150)]
    private string $nome;

    #[ORM\Column(name: 'desc_componente', type: 'text', nullable: true)]
    private ?string $descricao = null;

    #[ORM\ManyToOne(targetEntity: Categoria::class, inversedBy: 'componentes')]
    #[ORM\JoinColumn(name: 'id_categoria', referencedColumnName: 'id_categoria', nullable: false, onDelete: 'RESTRICT')]
    private ?Categoria $categoria = null;

    /** @var Collection<int, Equipamento> */
    #[ORM\ManyToMany(targetEntity: Equipamento::class, mappedBy: 'componentes')]
    private Collection $equipamentos;

    public function __construct(string $nome = '', ?Categoria $categoria = null, ?string $descricao = null)
    {
        $this->nome = $nome;
        $this->categoria = $categoria;
        $this->descricao = $descricao;
        $this->equipamentos = new ArrayCollection();
    }

    public function getCodigo(): ?int
    {
        return $this->codigo;
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

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): self
    {
        $this->descricao = $descricao;

        return $this;
    }

    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(Categoria $categoria): self
    {
        $this->categoria = $categoria;

        return $this;
    }

    /**
     * @return Collection<int, Equipamento>
     */
    public function getEquipamentos(): Collection
    {
        return $this->equipamentos;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $withCategoria = true): array
    {
        $data = [
            'codigo_componente' => $this->codigo,
            'nome_componente' => $this->nome,
            'desc_componente' => $this->descricao,
        ];

        if ($withCategoria) {
            $data['id_categoria'] = $this->categoria?->getId();
            $data['categoria'] = $this->categoria?->toArray();
        }

        return $data;
    }
}
