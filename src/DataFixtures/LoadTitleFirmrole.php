<?php

namespace App\DataFixtures;

use App\Entity\TitleFirmrole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Load some test firm roles.
 */
class TitleFirmroleFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface {
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
            $tfr = new TitleFirmrole();
            $tfr->setFirm($this->getReference('firm.' . $i));
            $tfr->setTitle($this->getReference('title.' . $i));
            $tfr->setFirmrole($this->getReference('firmrole.' . $i));

            $manager->persist($tfr);
            $this->setReference('tfr.' . $i, $tfr);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return array(
            TitleFixtures::class,
            FirmFixtures::class,
            FirmroleFixtures::class,
        );
    }
}
