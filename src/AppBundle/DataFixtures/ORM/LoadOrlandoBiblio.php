<?php

namespace AppBundle\DataFixtures\ORM;


use AppBundle\Entity\OrlandoBiblio;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadOrlandoBiblio extends Fixture
{

    public function load(ObjectManager $manager)
    {
        for($i = 0; $i < 4; $i++) {
            $fixture = new OrlandoBiblio();
            $fixture->setOrlandoId($i + 100);
            $fixture->setWorkform('work form ' . $i);
            $fixture->setAuthor("A_ID = 20384 || STANDARD = Author $i || ROLE = EDITOR %%% A_ID = 19884 || STANDARD = Other Author $i || ROLE = AUTHOR");
            $fixture->setAnalyticStandardTitle("Title " . $i);
            $fixture->setMonographicStandardTitle('Title ' . $i);
            $fixture->setImprintDateOfPublication('1880');
            $manager->persist($fixture);
            $this->setReference('orlando.' . $i, $fixture);
        }
        $manager->flush();
    }
}