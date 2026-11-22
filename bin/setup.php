#!/usr/bin/env php
<?php declare(strict_types=1);

use App\Entity\Categoria;
use App\Entity\Componente;
use App\Entity\Equipamento;
use App\Entity\Usuario;
use Doctrine\ORM\Tools\SchemaTool;

$container = require dirname(__DIR__) . '/src/bootstrap.php';

/** @var \Doctrine\ORM\EntityManagerInterface $em */
$em = $container['em'];
/** @var callable(string, ?string=): ?string $env */
$env = $container['env'];
$seedDemo = filter_var($env('SEED_DEMO', 'true'), FILTER_VALIDATE_BOOL);

echo "[setup] atualizando schema do banco...\n";

$schemaTool = new SchemaTool($em);
$metadata = $em->getMetadataFactory()->getAllMetadata();
$schemaTool->updateSchema($metadata);

echo "[setup] schema atualizado.\n";

$categorias = [];
$nomesCategorias = [
    'Armazenamento',
    'Processamento',
    'Display',
    'Memória',
    'Fonte de Alimentação',
    'Periféricos',
];

$categoriaRepository = $em->getRepository(Categoria::class);

foreach ($nomesCategorias as $nomeCategoria) {
    $categoria = $categoriaRepository->findOneBy(['nome' => $nomeCategoria]);

    if ($categoria === null) {
        $categoria = new Categoria($nomeCategoria);
        $em->persist($categoria);
        echo "[setup] categoria criada: {$nomeCategoria}\n";
    }

    $categorias[$nomeCategoria] = $categoria;
}

$em->flush();

if ($seedDemo && $em->getRepository(Componente::class)->count([]) === 0) {
    echo "[setup] populando banco para testes...\n";

    $definicoes = [
        ['SSD NVMe 1TB', 'Armazenamento', 'Unidade de estado sólido com 1 TB de capacidade.'],
        ['HD SATA 2TB', 'Armazenamento', 'Disco rígido mecânico com 2 TB de capacidade.'],
        ['Processador Intel Core i7-13700', 'Processamento', 'Processador de 16 núcleos e 24 threads.'],
        ['Processador AMD Ryzen 7 7800X3D', 'Processamento', 'Processador de 8 núcleos focado em jogos.'],
        ['Monitor LED 24 polegadas', 'Display', 'Monitor Full HD com painel IPS.'],
        ['Memória RAM DDR5 16GB', 'Memória', 'Pente de memória DDR5 de 16 GB a 5600 MHz.'],
        ['Fonte ATX 500W', 'Fonte de Alimentação', 'Fonte de alimentação com certificação 80 Plus.'],
        ['Teclado ABNT2', 'Periféricos', 'Teclado mecânico com layout ABNT2.'],
    ];

    $componentes = [];

    foreach ($definicoes as [$nome, $nomeCategoria, $descricao]) {
        $componente = new Componente($nome, $categorias[$nomeCategoria], $descricao);
        $em->persist($componente);
        $componentes[$nome] = $componente;
    }

    $em->flush();

    $notebook = new Equipamento('Notebook Dell Inspiron 15');
    $notebook->replaceComponentes([
        $componentes['SSD NVMe 1TB'],
        $componentes['Processador Intel Core i7-13700'],
        $componentes['Monitor LED 24 polegadas'],
        $componentes['Memória RAM DDR5 16GB'],
    ]);
    $em->persist($notebook);

    $desktop = new Equipamento('Desktop Gamer AMD');
    $desktop->replaceComponentes([
        $componentes['HD SATA 2TB'],
        $componentes['SSD NVMe 1TB'],
        $componentes['Processador AMD Ryzen 7 7800X3D'],
        $componentes['Monitor LED 24 polegadas'],
        $componentes['Memória RAM DDR5 16GB'],
        $componentes['Fonte ATX 500W'],
        $componentes['Teclado ABNT2'],
    ]);
    $em->persist($desktop);

    $em->flush();

    echo "[setup] componentes e equipamentos de demonstração criados.\n";
}

$usuarioRepository = $em->getRepository(Usuario::class);
$admin = $usuarioRepository->findOneBy(['nomeUsuario' => 'admin']);

if ($admin === null) {
    $admin = new Usuario(
        'admin',
        'admin@n3.local',
        password_hash('admin123', PASSWORD_DEFAULT),
    );

    $em->persist($admin);
    $em->flush();

    echo "[setup] usuário administrador criado (usuario: admin / senha: admin123).\n";
}

echo "[setup] concluído. =)\n";
