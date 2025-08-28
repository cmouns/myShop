<?php

namespace App\EventSubscriber;

use App\Entity\Category;
use Cocur\Slugify\Slugify;
use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class CategorySlugSubscriber implements EventSubscriber
{
    private Slugify $slugify;

    public function __construct()
    {
        $this->slugify = new Slugify();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist, // Avant INSERT
            Events::preUpdate   // Avant UPDATE
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->updateSlug($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->updateSlug($args);
    }

    private function updateSlug(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Category) {
            return;
        }

        if ($entity->getName()) {
            $entity->setSlug($this->slugify->slugify($entity->getName()));
        }
    }
}
