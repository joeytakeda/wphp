<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\ORM\LoadFirmrole;
use AppBundle\DataFixtures\ORM\LoadTitleFirmrole;
use AppBundle\Entity\Firmrole;
use Nines\UserBundle\DataFixtures\ORM\LoadUser;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class FirmroleControllerTest extends BaseTestCase {
    protected function getFixtures() {
        return array(
            LoadUser::class,
            LoadFirmrole::class,
            LoadTitleFirmrole::class,
        );
    }

    public function testAnonIndex() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/firmrole/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(0, $crawler->selectLink('Add Firm Role')->count());
    }

    public function testUserIndex() {
        $client = $this->makeClient(array(
            'username' => 'user@example.com',
            'password' => 'secret',
        ));
        $crawler = $client->request('GET', '/firmrole/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(0, $crawler->selectLink('Add Firm Role')->count());
    }

    public function testAdminIndex() {
        $client = $this->makeClient(array(
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ));
        $crawler = $client->request('GET', '/firmrole/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->selectLink('Add Firm Role')->count());
    }

    public function testAnonTypeahead() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/firmrole/typeahead?q=name');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('text/html; charset=UTF-8', $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsStringIgnoringCase('Redirecting', $client->getResponse()->getContent());
    }

    public function testUserTypeahead() {
        $client = $this->makeClient(array(
            'username' => 'user@example.com',
            'password' => 'secret',
        ));
        $crawler = $client->request('GET', '/firmrole/typeahead?q=name');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Access denied.', $client->getResponse()->getContent());
    }

    public function testAdminTypeahead() {
        $client = $this->makeClient(array(
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ));
        $crawler = $client->request('GET', '/firmrole/typeahead?q=name');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $json = json_decode($client->getResponse()->getContent());
        $this->assertEquals(4, count($json));
    }

    public function testAnonShow() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/firmrole/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(0, $crawler->selectLink('Edit')->count());
        $this->assertEquals(0, $crawler->selectLink('Delete')->count());
    }

    public function testUserShow() {
        $client = $this->makeClient(array(
            'username' => 'user@example.com',
            'password' => 'secret',
        ));
        $crawler = $client->request('GET', '/firmrole/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(0, $crawler->selectLink('Edit')->count());
        $this->assertEquals(0, $crawler->selectLink('Delete')->count());
    }

    public function testAdminShow() {
        $client = $this->makeClient(array(
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ));
        $crawler = $client->request('GET', '/firmrole/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->selectLink('Edit')->count());
        $this->assertEquals(1, $crawler->selectLink('Delete')->count());
    }

    public function testAnonEdit() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/firmrole/1/edit');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testUserEdit() {
        $client = $this->makeClient(array(
            'username' => 'user@example.com',
            'password' => 'secret',
        ));
        $crawler = $client->request('GET', '/firmrole/1/edit');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminEdit() {
        $client = $this->makeClient(array(
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ));
        $formCrawler = $client->request('GET', '/firmrole/1/edit');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Update')->form(array(
            'firmrole[name]' => 'Cheese.',
        ));

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/firmrole/1'));
        $responseCrawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $responseCrawler->filter('td:contains("Cheese.")')->count());
    }

    public function testAnonNew() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/firmrole/new');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testUserNew() {
        $client = $this->makeClient(array(
            'username' => 'user@example.com',
            'password' => 'secret',
        ));
        $crawler = $client->request('GET', '/firmrole/new');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminNew() {
        $client = $this->makeClient(array(
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ));
        $formCrawler = $client->request('GET', '/firmrole/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $formCrawler->selectButton('Create')->form(array(
            'firmrole[name]' => 'Cheese.',
        ));

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $responseCrawler->filter('td:contains("Cheese.")')->count());
    }

    public function testAnonDelete() {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/firmrole/1/delete');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testUserDelete() {
        $client = $this->makeClient(array(
            'username' => 'user@example.com',
            'password' => 'secret',
        ));
        $crawler = $client->request('GET', '/firmrole/1/delete');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminDelete() {
        self::bootKernel();
        $em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $preCount = count($em->getRepository(Firmrole::class)->findAll());
        $client = $this->makeClient(array(
            'username' => 'admin@example.com',
            'password' => 'supersecret',
        ));
        $crawler = $client->request('GET', '/firmrole/1/delete');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());
        $responseCrawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $em->clear();
        $postCount = count($em->getRepository(Firmrole::class)->findAll());
        $this->assertEquals($preCount - 1, $postCount);
    }
}
