<?php

namespace App\Controller;

use App\Entity\Firmrole;
use App\Form\FirmroleType;
use App\Repository\FirmroleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nines\UtilBundle\Controller\PaginatorTrait;

/**
 * Firmrole controller.
 *
 * @Route("/firmrole")
 */
class FirmroleController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all Firmrole entities.
     *
     * @Route("/", name="firmrole_index", methods={"GET"})
     * @Template()
     *
     * @param Request $request
     * @param FirmroleRepository $repo
     *
     * @return array
     */
    public function indexAction(Request $request, EntityManagerInterface $em) {
        $dql = 'SELECT e FROM App:Firmrole e ORDER BY e.name';
        $query = $em->createQuery($dql);
        $firmroles = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25, array(
            'defaultSortFieldName' => 'e.name',
            'defaultSortDirection' => 'asc',
        ));

        return array(
            'firmroles' => $firmroles,
            'repo' => $em->getRepository(Firmrole::class),
        );
    }

    /**
     * Typeahead action for editor widgets.
     *
     * @param Request $request
     * @param FirmroleRepository $repo
     *
     * @return JsonResponse
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     * @Route("/typeahead", name="firmrole_typeahead", methods={"GET"})
     */
    public function typeaheadAction(Request $request, FirmroleRepository $repo) {
        $q = $request->query->get('q');
        if ( ! $q) {
            return new JsonResponse(array());
        }
        $data = array();
        foreach ($repo->typeaheadQuery($q) as $result) {
            $data[] = array(
                'id' => $result->getId(),
                'text' => $result->getName(),
            );
        }

        return new JsonResponse($data);
    }

    /**
     * Creates a new Firmrole entity.
     *
     * @Route("/new", name="firmrole_new", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     * @Template()
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function newAction(Request $request, EntityManagerInterface $em) {
        $firmrole = new Firmrole();
        $form = $this->createForm(FirmroleType::class, $firmrole);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($firmrole);
            $em->flush();

            $this->addFlash('success', 'The new firmrole was created.');

            return $this->redirectToRoute('firmrole_show', array('id' => $firmrole->getId()));
        }

        return array(
            'firmrole' => $firmrole,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Firmrole entity.
     *
     * @Route("/{id}", name="firmrole_show", methods={"GET"})
     * @Template()
     *
     * @param Request $request
     * @param Firmrole $firmrole
     *
     * @return array
     */
    public function showAction(Request $request, Firmrole $firmrole, EntityManagerInterface $em) {
        $dql = 'SELECT tfr FROM App:TitleFirmrole tfr WHERE tfr.firmrole = :firmrole';
        $query = $em->createQuery($dql);
        $query->setParameter('firmrole', $firmrole);
        $titleFirmRoles = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25);

        return array(
            'firmrole' => $firmrole,
            'titleFirmRoles' => $titleFirmRoles,
        );
    }

    /**
     * Displays a form to edit an existing Firmrole entity.
     *
     * @Route("/{id}/edit", name="firmrole_edit", methods={"GET", "POST"})
     * @Template()
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     *
     * @param Request $request
     * @param Firmrole $firmrole
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, Firmrole $firmrole, EntityManagerInterface $em) {
        $editForm = $this->createForm(FirmroleType::class, $firmrole);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The firmrole has been updated.');

            return $this->redirectToRoute('firmrole_show', array('id' => $firmrole->getId()));
        }

        return array(
            'firmrole' => $firmrole,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Firmrole entity.
     *
     * @Route("/{id}/delete", name="firmrole_delete", methods={"GET"})
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     *
     * @param Request $request
     * @param Firmrole $firmrole
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, Firmrole $firmrole, EntityManagerInterface $em) {
        $em->remove($firmrole);
        $em->flush();
        $this->addFlash('success', 'The firmrole was deleted.');

        return $this->redirectToRoute('firmrole_index');
    }
}
