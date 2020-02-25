<?php

namespace App\DataFixtures;

use App\Entity\Firmrole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some test firm roles.
 */
class FirmroleFixtures extends Fixture implements FixtureGroupInterface {
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
            $fixture = new Firmrole();
            $fixture->setName('Name ' . $i);

            $manager->persist($fixture);
            $this->setReference('firmrole.' . $i, $fixture);
        }

        $manager->flush();
    }
}
