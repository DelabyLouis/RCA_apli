<?php

// Préchargement optimisé pour Symfony en production
// Ce fichier améliore les performances en pré-chargeant les classes fréquemment utilisées

if (file_exists(dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php')) {
    require dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php';
}

// Préchargement des entités critiques
$entitiesDir = dirname(__DIR__) . '/src/Entity';
if (is_dir($entitiesDir)) {
    $entities = [
        'User.php',
        'Transaction.php', 
        'Exercice.php',
        'Entreprise.php',
        'Personne.php'
    ];
    
    foreach ($entities as $entity) {
        $entityPath = $entitiesDir . '/' . $entity;
        if (file_exists($entityPath)) {
            opcache_compile_file($entityPath);
        }
    }
}

// Préchargement des contrôleurs principaux
$controllersDir = dirname(__DIR__) . '/src/Controller';
if (is_dir($controllersDir)) {
    $controllers = [
        'HomeController.php',
        'SecurityController.php',
        'TransactionController.php'
    ];
    
    foreach ($controllers as $controller) {
        $controllerPath = $controllersDir . '/' . $controller;
        if (file_exists($controllerPath)) {
            opcache_compile_file($controllerPath);
        }
    }
}
