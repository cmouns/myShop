<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class FixProductSlugsCommand extends Command
{
    // Ne pas utiliser $defaultName ici car le constructeur a des arguments
    protected function configure(): void
    {
        $this
            ->setName('app:fix-product-slugs')
            ->setDescription('Régénère tous les slugs produits pour éviter les caractères invalides.');
    }

    private EntityManagerInterface $em;
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger)
    {
        parent::__construct();
        $this->em = $em;
        $this->slugger = $slugger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->em->getRepository(Product::class)->findAll();

        foreach ($products as $product) {
            $name = $product->getName();

            // Remplacer / et \ par des tirets
            $cleanName = preg_replace('/[\/\\\]+/', '-', $name);

            // Supprimer ou remplacer les caractères spéciaux
            $cleanName = preg_replace('/[^\p{L}\p{N}\- ]+/u', '', $cleanName); // laisse lettres, chiffres, espaces et tirets

            // Génération du slug avec le Slugger Symfony
            $slug = strtolower($this->slugger->slug($cleanName));

            $product->setSlug($slug);

            $output->writeln("Produit #{$product->getId()} : nouveau slug -> $slug");
        }

        $this->em->flush();

        $output->writeln('✅ Tous les slugs produits ont été régénérés.');
        return Command::SUCCESS;
    }
}
