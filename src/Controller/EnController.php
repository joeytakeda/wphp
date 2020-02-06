<?php

namespace App\Controller;

use App\Entity\En;
use App\Repository\EnRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nines\UtilBundle\Controller\PaginatorTrait;

/**
 * En controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/resource/en")
 */
class EnController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all En entities.
     *
     * @param Request $request
     *
     * @return array
     *
     * @Route("/", name="resource_en_index", methods={"GET"})
     * @Template()
     */
    public function indexAction(Request $request, EntityManagerInterface $em) {
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(En::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $ens = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25);

        return array(
            'ens' => $ens,
        );
    }

    /**
     * Search for En entities.
     *
     * @param Request $request
     * @param EnRepository $repo
     *
     * @return array
     * @Route("/search", name="resource_en_search", methods={"GET"})
     * @Template()
     */
    public function searchAction(Request $request, EnRepository $repo) {
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $ens = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $ens = array();
        }

        return array(
            'ens' => $ens,
            'q' => $q,
        );
    }

    /**
     * Finds and displays a En entity.
     *
     * @param En $en
     *
     * @return array
     *
     * @Route("/{id}", name="resource_en_show", methods={"GET"})
     *
     * @Template()
     */
    public function showAction(En $en) {
        return array(
            'en' => $en,
        );
    }
}