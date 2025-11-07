<?php

namespace App\Tests\Entity;

use App\Entity\Transaction;
use App\Entity\Exercice;
use App\Entity\TypeTransaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testTransactionCreation(): void
    {
        $transaction = new Transaction();
        $transaction->setLibelle('Test Transaction');
        $transaction->setMontant('100.50');
        $transaction->setDateTransaction(new \DateTime('2025-01-15'));
        $transaction->setNumeroOrdre(1);
        
        $this->assertEquals('Test Transaction', $transaction->getLibelle());
        $this->assertEquals('100.50', $transaction->getMontant());
        $this->assertEquals(1, $transaction->getNumeroOrdre());
        $this->assertEquals('2025-01-15', $transaction->getDateTransaction()->format('Y-m-d'));
    }

    public function testTransactionWithExercice(): void
    {
        $transaction = new Transaction();
        $exercice = new Exercice();
        $exercice->setLibelle('Exercice 2025');
        
        $transaction->setExercice($exercice);
        
        $this->assertEquals($exercice, $transaction->getExercice());
    }

    public function testTransactionWithTypeTransaction(): void
    {
        $transaction = new Transaction();
        $typeTransaction = new TypeTransaction();
        $typeTransaction->setLibelle('Recette');
        
        $transaction->setTypeTransaction($typeTransaction);
        
        $this->assertEquals($typeTransaction, $transaction->getTypeTransaction());
    }

    public function testTransactionMontantValidation(): void
    {
        $transaction = new Transaction();
        
        // Test montant positif
        $transaction->setMontant('100.50');
        $this->assertEquals('100.50', $transaction->getMontant());
        
        // Test montant négatif
        $transaction->setMontant('-50.25');
        $this->assertEquals('-50.25', $transaction->getMontant());
    }

    public function testTransactionTypeCompte(): void
    {
        $transaction = new Transaction();
        
        // Test valeur par défaut
        $this->assertEquals('compte_courant', $transaction->getTypeCompte());
        
        // Test modification
        $transaction->setTypeCompte('livret_a');
        $this->assertEquals('livret_a', $transaction->getTypeCompte());
    }
}