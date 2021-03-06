<?php

namespace OrganizerBundle\Controller;

use AppBundle\Controller\AjaxAPIController;
use AppBundle\Entity\Helper;
use AppBundle\Entity\Poi;
use AppBundle\Entity\Raid;
use AppBundle\Service\HelperService;
use OrganizerBundle\Security\RaidVoter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizerPOIController extends AjaxAPIController
{
    /**
     * @Route("/editor/raid/{raidId}/poi", name="addPoi", methods={"PUT"})
     *
     * @param Request $request request
     * @param int     $raidId  raid identifier
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addPoi(Request $request, $raidId)
    {
        // Set up managers
        $em = $this->getDoctrine()->getManager();

        $raidManager = $em->getRepository('AppBundle:Raid');

        // Find the user
        $user = $this->get('security.token_storage')->getToken()->getUser();

        //$raidId = (int) $raidId;

        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'You are not allowed to access this raid');
        }

        $data = $request->request->all();
        $poiService = $this->container->get('PoiService');

        if (!$poiService->checkDataArray($data, false)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'Tous les champs doivent être remplis.');
        }

        $poi = $poiService->poiFromArray($data, $raid->getId());

        $em->persist($poi);
        $em->flush();

        return new Response($poiService->poiToJson($poi));
    }

    /**
     * @Route("/editor/raid/{raidId}/poi/{poiId}", name="displayPoi", methods={"PATCH"})
     *
     * @param Request $request request
     * @param int     $raidId  raid identifier
     * @param int     $poiId   poi identifier
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayPoi(Request $request, $raidId, $poiId)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $raidManager = $em->getRepository('AppBundle:Raid');

        // Find the user
        $user = $this->get('security.token_storage')->getToken()->getUser();

        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'You are not allowed to access this raid');
        }

        $data = $request->request->all();
        $poiService = $this->container->get('PoiService');

        if (!$poiService->checkDataArray($data, false)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'Tous les champs doivent être remplis.');
        }

        $poiManager = $em->getRepository('AppBundle:Poi');
        $poi = $poiManager->find($poiId);

        if (null != $poi) {
            $poi = $poiService->updatePoiFromArray($poi, $raid->getId(), $data);
            $em->flush();
        } else {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'Ce point d\'intérêt n\'existe pas.');
        }

        //Log change on db
        $raid->notifyChange($user, $em);

        return new Response($poiService->poiToJson($poi));
    }

    /**
     * @Route("/editor/raid/{raidId}/poi/{poiId}", name="deletePoi", methods={"DELETE"})
     *
     * @param Request $request request
     * @param mixed   $raidId  raid identifier
     * @param id      $poiId   poi identifier
     *
     * @return Response
     */
    public function deletePoi(Request $request, $raidId, $poiId)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $raidManager = $em->getRepository('AppBundle:Raid');

        // Find the user
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));
        //$raid = $raidManager->findOneBy(array('id' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'You are not allowed to access this raid');
        }

        $poiManager = $em->getRepository('AppBundle:Poi');
        $poi = $poiManager->find($poiId);

        $helperManager = $em->getRepository('AppBundle:Helper');
        $helpers = $helperManager->findBy(['poi' => $poi]);

        foreach ($helpers as $helper) {
            if (null != $helper) {
                $helper->setPoi(null);
                $em->flush();
            }
        }

        if (null != $poi) {
            $em->remove($poi);
            $em->flush();
        } else {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'Ce point d\'intérêt n\'existe pas.');
        }

        return parent::buildJSONStatus(Response::HTTP_OK, 'Point d\'intérêt supprimé.');
    }

    /**
     * @Route("/editor/raid/{raidId}/poi", name="listPoiAPI", methods={"GET"})
     *
     * @param mixed $raidId raid identifier
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listPoisAPI($raidId)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $poiManager = $em->getRepository('AppBundle:Poi');
        $raidManager = $em->getRepository('AppBundle:Raid');
        $helperManager = $em->getRepository('AppBundle:Helper');

        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(['uniqid' => $raidId]);

        // Get the user
        $user = $this->get('security.token_storage')->getToken()->getUser();

        /** @var HelperService $helperService */
        $helperService = $this->container->get('HelperService');

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $authChecker = $this->get('security.authorization_checker');

        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid) && !$authChecker->isGranted(RaidVoter::HELPER, $raid)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'You are not allowed to access this raid');
        } elseif ($authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            $pois = $poiManager->findBy(array('raid' => $raid));
            /** @var Poi $poi */
            foreach ($pois as $poi) {
                $helpers = $helperManager->findBy(['poi' => $poi]);
                $helpersArray = [];
                /** @var Helper $helper */
                foreach ($helpers as $helper) {
                    $usr = $helper->getUser();

                    $h = [];
                    $h['firstname'] = $usr->getFirstName();
                    $h['lastname'] = $usr->getLastName();
                    $h['phone'] = $usr->getPhone();

                    $helpersArray[] = $h;
                }
                $poi->setHelpers($helpersArray);
            }
        } else {
            $helper = $helperManager->findOneBy(['user' => $user, 'raid' => $raid]);

            if (!is_null($helper) && !is_null($helper->getPoi())) {
                $pois[] = $poiManager->find($helper->getPoi());
            } else {
                $pois = [];
            }
        }

        $poiService = $this->container->get('PoiService');

        return new Response($poiService->poisArrayToJson($pois));
    }

    /**
     * @Route("/organizer/raid/{raidId}/pois", name="listPois", methods={"GET"})
     *
     * @param mixed $raidId raid identifier
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listPoi($raidId)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $poiManager = $em->getRepository('AppBundle:Poi');

        $raidManager = $em->getRepository('AppBundle:Raid');

        $raid = $raidManager->findOneBy(['uniqid' => $raidId]);

        // Get the user
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (null == $user->getId()) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'Accès refusé.');
        }

        $pois = $poiManager->findBy(array('raid' => $raid));

        return $this->render(
            'OrganizerBundle:Poi:listPoi.html.twig',
            [
                'raidId' => $raidId,
                'pois' => $pois,
                'user' => $user,
                'raid' => $raid,
            ]
        );
    }
}
