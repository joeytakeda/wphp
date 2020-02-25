<?php

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadEn;
use AppBundle\DataFixtures\ORM\LoadEstcMarc;
use AppBundle\DataFixtures\ORM\LoadJackson;
use AppBundle\DataFixtures\ORM\LoadOrlandoBiblio;
use AppBundle\DataFixtures\ORM\LoadOsborneMarc;
use AppBundle\Entity\Source;
use AppBundle\Services\RoleChecker;
use AppBundle\Services\SourceLinker;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class SourceLinkerTest extends BaseTestCase {
    /**
     * @var SourceLinker
     */
    private $linker;

    private $checker;

    protected function getFixtures() {
        return array(
            LoadEstcMarc::class,
            LoadOrlandoBiblio::class,
            LoadJackson::class,
            LoadEn::class,
            LoadOsborneMarc::class,
        );
    }

    public function testSanity() {
        $this->assertInstanceOf(SourceLinker::class, $this->linker);
    }

    public function testEstcAnon() {
        $this->checker->method('hasRole')->willReturn(false);
        $this->assertNull($this->linker->estc('abc'));
    }

    public function testEstcUser() {
        $this->assertStringEndsWith('resource/estc/1', $this->linker->estc('abc-0'));
    }

    public function testEstcNotFound() {
        $this->assertNull($this->linker->estc('abc-123456'));
    }

    public function testOrlandoAnon() {
        $this->checker->method('hasRole')->willReturn(false);
        $this->assertNull($this->linker->orlando('abc'));
    }

    public function testOrlandoUser() {
        $this->assertStringEndsWith('resource/orlando_biblio/1', $this->linker->orlando('100'));
    }

    public function testOrlandoNotFound() {
        $this->assertNull($this->linker->orlando('800'));
    }

    public function testJacksonAnon() {
        $this->checker->method('hasRole')->willReturn(false);
        $this->assertStringEndsWith('details/abc', $this->linker->jackson('abc'));
    }

    public function testJacksonUser() {
        $this->assertStringEndsWith('resource/jackson/1', $this->linker->jackson('1234'));
    }

    public function testJacksonNotFound() {
        $this->assertStringEndsWith('details/abc123', $this->linker->jackson('abc123'));
    }

    public function testEnAnon() {
        $this->checker->method('hasRole')->willReturn(false);
        $this->assertNull($this->linker->en('abc'));
    }

    public function testEnUser() {
        $this->assertStringEndsWith('resource/en/1', $this->linker->en('en-0'));
    }

    public function testEnNotFound() {
        $this->assertNull($this->linker->en('abc-123456'));
    }

    public function testOsborneAnon() {
        $this->checker->method('hasRole')->willReturn(false);
        $this->assertNull($this->linker->osborne('abc'));
    }

    public function testOsborneUser() {
        $this->assertStringEndsWith('resource/osborne/1', $this->linker->osborne('abc-0'));
    }

    public function testOsborneNotFound() {
        $this->assertNull($this->linker->osborne('abc-123456'));
    }

    public function testEcco() {
        $this->assertStringContainsString('aa0aaaa', $this->linker->ecco('aaaaaa'));
    }

    /**
     * @dataProvider urlData
     *
     * @param mixed $expected
     * @param mixed $name
     * @param mixed $data
     */
    public function testUrl($expected, $name, $data) {
        $source = $this->createMock(Source::class);
        $source->method('getName')->willReturn($name);
        $actual = $this->linker->url($source, $data);
        if ($expected) {
            $this->assertRegExp($expected, $actual);
        } else {
            $this->assertNull($actual);
        }
    }

    public function urlData() {
        return array(
            array('/^https\:\/\/example.com/', 'ESTC', 'https://example.com/foo/bar'),
            array('/^https\:\/\/example.com/', 'cheesery', 'https://example.com/foo/bar'),

            array('{resource/estc/1$}', 'ESTC', 'abc-0'),
            array(null, 'ESTC', 'abcdef'),

            array('{resource/orlando_biblio/1$}', 'Orlando', '100'),
            array(null, 'Orlando', 'abcdef'),

            array('{resource/jackson/1$}', 'Jackson Bibliography', '1234'),
            array('{details/abcdef$}', 'Jackson Bibliography', 'abcdef'),

            array('{resource/en/1$}', 'The English Novel 1770-1829', 'en-0'),
            array(null, 'The English Novel 1770-1829', 'abcdef'),
            array('{resource/en/1$}', 'The English Novel 1830-1836', 'en-0'),
            array(null, 'The English Novel 1830-1836', 'abcdef'),

            array('{resource/osborne/1$}', 'Osborne Collection of Early Children\'s Books', 'abc-0'),
            array(null, 'Osborne Collection of Early Children\'s Books', 'abcdef'),

            array('{ab0cdef}', 'ECCO', 'abcdef'),

            //            ['/^https\:\/\/example.com/', 'ESTC', 'https://example.com/foo/bar'],
            //            ['/^https\:\/\/example.com/', 'ESTC', 'https://example.com/foo/bar'],
        );
    }

    protected function setUp() : void {
        parent::setUp();
        $this->checker = $this->createMock(RoleChecker::class);
        $this->checker->method('hasRole')->willReturn(true);
        $this->linker = $this->getContainer()->get(SourceLinker::class);
        $this->linker->setRoleChecker($this->checker);
    }
}
