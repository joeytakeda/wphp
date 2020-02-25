<?php

namespace App\Controller;

use App\Entity\Title;
use App\Form\Title\TitleSearchType;
use App\Form\Title\TitleType;
use App\Repository\TitleRepository;
use App\Services\EstcMarcImporter;
use App\Services\SourceLinker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Nines\UtilBundle\Controller\PaginatorTrait;

/**
 * Title controller.
 *
 * @Route("/title")
 */
class TitleController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all Title entities.
     *
     * @Route("/", name="title_index", methods={"GET"})
     *
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request, EntityManagerInterface $em) {
        $dql = 'SELECT e FROM App:Title e';
        if (null === $this->getUser()) {
            $dql .= ' WHERE (e.finalcheck = 1 OR e.finalattempt = 1)';
        }
        $query = $em->createQuery($dql);

        $form = $this->createForm(TitleSearchType::class, null, array(
            'action' => $this->generateUrl('title_search'),
            'entity_manager' => $em,
            'user' => $this->getUser(),
        ));
        $titles = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25, array(
            'defaultSortFieldName' => array('e.title', 'e.pubdate'),
            'defaultSortDirection' => 'asc',
        ));

        return array(
            'search_form' => $form->createView(),
            'titles' => $titles,
            'sortable' => true,
        );
    }

    /**
     * Search for titles and return typeahead-widget-friendly JSON.
     *
     * @param Request $request
     * @param TitleRepository $repo
     *
     * @return JsonResponse
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     * @Route("/typeahead", name="title_typeahead", methods={"GET"})
     */
    public function typeaheadAction(Request $request, TitleRepository $repo) {
        $q = $request->query->get('q');
        if ( ! $q) {
            return new JsonResponse(array());
        }
        $data = array();
        foreach ($repo->typeaheadQuery($q) as $result) {
            $data[] = array(
                'id' => $result->getId(),
                'text' => $result->getTitle(),
            );
        }

        return new JsonResponse($data);
    }

    /**
     * Export a CSV with the titles.
     *
     * @Route("/export", name="title_export", methods={"GET"})
     *
     * @return BinaryFileResponse
     */
    public function exportAction(EntityManagerInterface $em) {
        $dql = 'SELECT e FROM App:Title e ORDER BY e.id';
        if (null === $this->getUser()) {
            $dql .= ' WHERE (e.finalcheck = 1 OR e.finalattempt = 1)';
        }
        $query = $em->createQuery($dql);
        $iterator = $query->iterate();
        $tmpPath = tempnam(sys_get_temp_dir(), 'wphp-export-');
        $fh = fopen($tmpPath, 'w');
        fputcsv($fh, array(
            'id',
            'title',
            'signed_author',
            'pseudonym',
            'imprint',
            'selfpublished',
            'printing_city',
            'printing_country',
            'printing_lat',
            'printing_long',
            'pubdate',
            'format',
            'length',
            'width',
            'edition',
            'volumes',
            'pagination',
            'price_pound',
            'price_shilling',
            'price_pence',
            'genre',
            'shelfmark',
        ));
        foreach ($iterator as $row) {
            $title = $row[0];
            fputcsv($fh, array(
                $title->getId(),
                $title->getTitle(),
                $title->getSignedAuthor(),
                $title->getPseudonym(),
                $title->getImprint(),
                $title->getSelfPublished() ? 'yes' : 'no',
                ($title->getLocationOfPrinting() ? $title->getLocationOfPrinting()->getName() : ''),
                ($title->getLocationOfPrinting() ? $title->getLocationOfPrinting()->getCountry() : ''),
                ($title->getLocationOfPrinting() ? $title->getLocationOfPrinting()->getLatitude() : ''),
                ($title->getLocationOfPrinting() ? $title->getLocationOfPrinting()->getLongitude() : ''),
                $title->getPubDate(),
                ($title->getFormat() ? $title->getFormat()->getName() : ''),
                $title->getSizeL(),
                $title->getSizeW(),
                $title->getEdition(),
                $title->getVolumes(),
                $title->getPagination(),
                $title->getPricePound(),
                $title->getPriceShilling(),
                $title->getPricePence(),
                ($title->getGenre() ? $title->getGenre()->getName() : ''),
                $title->getShelfmark(),
            ));
        }
        fclose($fh);
        $response = new BinaryFileResponse($tmpPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'wphp-titles.csv');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Full text search for Title entities.
     *
     * @Route("/search", name="title_search", methods={"GET"})
     * @Template()
     *
     * @param Request $request
     * @param TitleRepository $repo
     *
     * @return array
     */
    public function searchAction(Request $request, TitleRepository $repo) {
        $form = $this->createForm(TitleSearchType::class, null, array(
            'entity_manager' => $em,
            'user' => $this->getUser(),
        ));
        $form->handleRequest($request);
        $titles = array();
        $submitted = false;

        if ($form->isValid()) {
            $data = array_filter($form->getData());
            if (count($data) > 2) {
                $submitted = true;
                $query = $repo->buildSearchQuery($data, $this->getUser());
                $titles = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25);
            }
        }

        return array(
            'search_form' => $form->createView(),
            'titles' => $titles,
            'submitted' => $submitted,
        );
    }

    /**
     * Full text search for Title entities.
     *
     * @Route("/search/export", name="title_search_export", methods={"GET"})
     * @Template()
     *
     * @param Request $request
     * @param TitleRepository $repo
     *
     * @return array
     */
    public function searchExportAction(Request $request, TitleRepository $repo) {
        $form = $this->createForm(TitleSearchType::class, null, array(
            'entity_manager' => $em,
            'user' => $this->getUser(),
        ));
        $form->handleRequest($request);
        $titles = array();

        if ($form->isValid()) {
            $query = $repo->buildSearchQuery($form->getData(), $this->getUser());
            $titles = $query->execute();
        }

        return array(
            'titles' => $titles,
            'format' => $request->query->get('format', 'mla'),
        );
    }

    /**
     * Creates a new Title entity.
     *
     * @Route("/new", name="title_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     * @Template()
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     *
     * @return array|RedirectResponse
     */
    public function newAction(Request $request, EntityManagerInterface $em) {
        $title = new Title();
        $form = $this->createForm(TitleType::class, $title);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // check for new titleFirmRoles and persist them.
            foreach ($title->getTitleFirmroles() as $tfr) {
                $tfr->setTitle($title);
                $em->persist($tfr);
            }

            // check for new titleFirmRoles and persist them.
            foreach ($title->getTitleroles() as $tr) {
                $tr->setTitle($title);
                $em->persist($tr);
            }
            foreach ($title->getTitleSources() as $ts) {
                $ts->setTitle($title);
                $em->persist($ts);
            }
            $em->persist($title);
            $em->flush();

            $this->addFlash('success', 'The new title was created.');

            return $this->redirectToRoute('title_show', array('id' => $title->getId()));
        }

        return array(
            'title' => $title,
            'form' => $form->createView(),
        );
    }

    /**
     * Build a new title form prepopulated with data from a MARC record.
     *
     * @param Request $request
     * @param EstcMarcImporter $importer
     * @param string $id
     *
     * @return array
     * @Route("/import/{id}", name="title_marc_import", methods={"GET"})
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     * @Template("App:title:new.html.twig")
     */
    public function importMarcAction(Request $request, EstcMarcImporter $importer, $id) {
        $title = $importer->import($id);
        foreach ($importer->getMessages() as $message) {
            $this->addFlash('warning', $message);
        }
        $importer->resetMessages();

        $form = $this->createForm(TitleType::class, $title, array(
            'action' => $this->generateUrl('title_new'),
        ));

        return array(
            'title' => $title,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Title entity.
     *
     * @Route("/{id}.{_format}", name="title_show", defaults={"_format": "html"}, methods={"GET"})
     * @Template()
     *
     * @param Title $title
     * @param SourceLinker $linker
     *
     * @return array
     */
    public function showAction(Title $title, SourceLinker $linker) {
        if ( ! $this->getUser() && ! $title->getFinalattempt() && ! $title->getFinalcheck()) {
            throw new AccessDeniedHttpException('This title has not been verified and is not available to the public.');
        }

        return array(
            'title' => $title,
            'linker' => $linker,
        );
    }

    /**
     * Displays a form to edit an existing Title entity.
     *
     * @Route("/{id}/edit", name="title_edit", methods={"GET","POST"})
     * @Template()
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     *
     * @param Request $request
     * @param Title $title
     * @param EntityManagerInterface $em
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, Title $title, EntityManagerInterface $em) {
        // collect the titleFirmRole objects before modification.
        $titleFirmRoles = new ArrayCollection();
        foreach ($title->getTitleFirmroles() as $tfr) {
            $titleFirmRoles->add($tfr);
        }

        $titleRoles = new ArrayCollection();
        foreach ($title->getTitleroles() as $tr) {
            $titleRoles->add($tr);
        }
        $titleSources = new ArrayCollection();
        foreach ($title->getTitleSources() as $ts) {
            $titleSources->add($ts);
        }

        $editForm = $this->createForm(TitleType::class, $title);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // check for deleted titleFirmRoles and remove them.
            foreach ($titleFirmRoles as $tfr) {
                if ( ! $title->getTitleFirmroles()->contains($tfr)) {
                    $em->remove($tfr);
                }
            }

            // check for deleted titleRoles and remove them.
            foreach ($titleRoles as $tr) {
                if ( ! $title->getTitleroles()->contains($tr)) {
                    $em->remove($tr);
                }
            }

            foreach ($titleSources as $ts) {
                if ( ! $title->getTitleSources()->contains($ts)) {
                    $em->remove($ts);
                }
            }

            // check for new titleFirmRoles and persist them.
            foreach ($title->getTitleroles() as $tr) {
                if ( ! $titleRoles->contains($tr)) {
                    $tr->setTitle($title);
                    $em->persist($tr);
                }
            }

            // check for new titleFirmRoles and persist them.
            foreach ($title->getTitleFirmroles() as $tfr) {
                if ( ! $titleFirmRoles->contains($tfr)) {
                    $tfr->setTitle($title);
                    $em->persist($tfr);
                }
            }

            foreach ($title->getTitleSources() as $ts) {
                if ( ! $titleSources->contains($ts)) {
                    $ts->setTitle($title);
                    $em->persist($ts);
                }
            }

            $em->flush();
            $this->addFlash('success', 'The title has been updated.');

            return $this->redirectToRoute('title_show', array('id' => $title->getId()));
        }

        return array(
            'title' => $title,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Title entity.
     *
     * @Route("/{id}/copy", name="title_copy", methods={"GET","POST"})
     * @Template()
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     *
     * @param Request $request
     * @param Title $title
     * @param EntityManagerInterface $em
     *
     * @return array
     */
    public function copyAction(Request $request, Title $title, EntityManagerInterface $em) {
        $form = $this->createForm(TitleType::class, $title, array(
            'action' => $this->generateUrl('title_new'),
        ));

        return array(
            'title' => $title,
            'form' => $form->createView(),
        );
    }

    /**
     * Deletes a Title entity.
     *
     * @Route("/{id}/delete", name="title_delete", methods={"GET"})
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     *
     * @param Request $request
     * @param Title $title
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, Title $title, EntityManagerInterface $em) {
        foreach ($title->getTitleFirmroles() as $tfr) {
            $em->remove($tfr);
        }
        foreach ($title->getTitleRoles() as $tr) {
            $em->remove($tr);
        }
        foreach ($title->getTitleSources() as $ts) {
            $em->remove($ts);
        }
        $em->remove($title);
        $em->flush();
        $this->addFlash('success', 'The title was deleted.');

        return $this->redirectToRoute('title_index');
    }
}
