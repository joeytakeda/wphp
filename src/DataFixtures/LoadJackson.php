<?php

namespace App\DataFixtures;

use App\Entity\Jackson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some test Jackson Bibliography data.
 */
class JacksonFixtures extends Fixture implements FixtureGroupInterface {
    /**
     * {@inheritdoc}
     */
    public static function getGroups() : array {
        return array('test');
    }

    /**
     * {@inheritdoc}
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager) {
        for ($i = 0; $i < 4; $i++) {
            $fixture = new Jackson();
            $fixture->setJbid(1234 + $i);
            $fixture->setAuthor('Author ' . $i);
            $fixture->setTitle('Title ' . $i);
            $fixture->setDetailedEntry('Entry ' . $i);
            $fixture->setExamnote('Note ' . $i);
            $manager->persist($fixture);
            $this->setReference('jackson.' . $i, $fixture);
        }
        $manager->flush();
    }
}
