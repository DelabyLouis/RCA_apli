<?php
require __DIR__ . '/../vendor/autoload.php';
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Transaction;

// load environment variables from .env so DATABASE_URL is set
if (file_exists(__DIR__.'/../.env')) {
    (new Dotenv())->load(__DIR__.'/../.env');
}
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.default_entity_manager');
$repo = $em->getRepository(Transaction::class);
$qb = $repo->createQueryBuilder('t')->leftJoin('t.exercice','ex')->addSelect('ex');
$type = 'credit';
if ($type === 'credit') {
    $qb->andWhere('( (t.typeCompte != :livret AND t.montant > 0) OR (t.typeCompte = :livret AND t.montant < 0) )')
       ->setParameter('livret','livret');
}
echo "DQL=" . $qb->getQuery()->getDQL() . "\n";
