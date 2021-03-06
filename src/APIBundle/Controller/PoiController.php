<?php

namespace APIBundle\Controller;

use AppBundle\Controller\AjaxAPIController;
use FOS\RestBundle\Controller\Annotations as Rest;
use OrganizerBundle\Security\RaidVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PoiController extends AjaxAPIController
{
    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Get("/api/organizer/raid/{raidId}/poi")
     *
     * @param Request $request
     * @param int     $raidId  raid id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPoisAction(Request $request, $raidId)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $poiManager = $em->getRepository('AppBundle:Poi');
        $raidManager = $em->getRepository('AppBundle:Raid');

        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        // Get the user
        $user = $this->get('security.token_storage')->getToken()->getUser();

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            throw $this->createAccessDeniedException();
        }

        $pois = $poiManager->findBy(array('raid' => $raid->getId()));
        $poiService = $this->container->get('PoiService');

        return new Response($poiService->poisArrayToJson($pois));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Get("/api/helper/raid/{raidId}/poi")
     *
     * @param Request $request
     * @param int     $raidId  raid id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getHelperPOIAction(Request $request, $raidId)
    {
        $em = $this->getDoctrine()->getManager();
        $raidManager = $em->getRepository('AppBundle:Raid');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        //$raid = $raidManager->find($raidId);
        $raid = $raidManager->findOneBy(['uniqid' => $raidId]);

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, "Ce raid n'existe pas");
        }

        $helperManager = $em->getRepository('AppBundle:Helper');
        $helper = $helperManager->findOneBy(['user' => $user, 'raid' => $raid]);

        $poiService = $this->container->get('PoiService');
        if (null != $helper && null != $helper->getPoi()) {
            return new Response($poiService->poiToJson($helper->getPoi()));
        }

        return parent::buildJSONStatus(
            Response::HTTP_NOT_FOUND,
            "Pas de point d'intérêt attribué pour cet utilisateur et ce raid"
        );
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Put("/api/helper/raid/{raidId}/check-in")
     *
     * @param Request $request
     * @param int     $raidId  raid id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putHelperCheckinAction(Request $request, $raidId)
    {
        // Set up managers
        $em = $this->getDoctrine()->getManager();

        $raidManager = $em->getRepository('AppBundle:Raid');

        // Find the user
        $user = $this->get('security.token_storage')->getToken()->getUser();

        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(['uniqid' => $raidId]);

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'This raid does not exist');
        }

        $helperManager = $em->getRepository('AppBundle:Helper');
        $helper = $helperManager->findOneBy(['user' => $user, 'raid' => $raid]);

        $reqLocation = $request->request->all();
        //calcul of distance

        // convert from degrees to radians
        $poiLat = deg2rad($helper->getPoi()->getLatitude());
        $poiLng = deg2rad($helper->getPoi()->getLongitude());
        $userLat = deg2rad(doubleval($reqLocation['lat']));
        $userLng = deg2rad(doubleval($reqLocation['lng']));

        $lonDelta = $userLng - $poiLng;
        $a = pow(cos($userLat) * sin($lonDelta), 2) +
        pow(cos($poiLat) * sin($userLat) - sin($poiLat) * cos($userLat) * cos($lonDelta), 2);
        $b = sin($poiLat) * sin($userLat) + cos($poiLat) * cos($userLat) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        $d = $angle * 6371000;

        if ($d > 10) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'Out of zone');
        }

        if (1 == $helper->getisCheckedIn()) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'You have already checked in for this raid');
        }

        $now = new \DateTime('now');

        $diff = $raid->getDate()->diff($now);
        if ($diff->days > 0 || (0 == $diff->invert && $diff->days > 0)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'You can not check in for this raid today');
        }
        $helper->setIsCheckedIn(1);
        $helper->setCheckInTime($now);
        $em->flush();

        $ret = [];
        $ret['checkInTime'] = $helper->getCheckInTime();
        $ret['code'] = Response::HTTP_OK;

        return new Response(json_encode($ret));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Put("/api/organizer/raid/{raidId}/poi")
     *
     * @param Request $request
     * @param int     $raidId  raid id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putPOIAction(Request $request, $raidId)
    {
        // Set up managers
        $em = $this->getDoctrine()->getManager();

        $raidManager = $em->getRepository('AppBundle:Raid');

        // Find the user
        $user = $this->get('security.token_storage')->getToken()->getUser();
        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(['uniqid' => $raidId]);

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'This raid does not exist');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            throw $this->createAccessDeniedException();
        }

        $data = $request->request->all();
        $poiService = $this->container->get('PoiService');

        if (!$poiService->checkDataArray($data, false)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'Every fields must be filled');
        }

        $poi = $poiService->poiFromArray($data, $raid->getId());

        $em->persist($poi);
        $em->flush();

        return new Response($poiService->poiToJson($poi));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Patch("/api/organizer/raid/{raidId}/poi/{poiId}")
     *
     * @param Request $request
     * @param int     $raidId  raid id
     * @param int     $poiId   poi id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchPOIAction(Request $request, $raidId, $poiId)
    {
        // Set up managers
        $em = $this->getDoctrine()->getManager();

        $raidManager = $em->getRepository('AppBundle:Raid');

        // Find the user
        $user = $this->get('security.token_storage')->getToken()->getUser();
        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(['uniqid' => $raidId]);

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'This raid does not exist');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            throw $this->createAccessDeniedException();
        }

        $data = $request->request->all();
        $poiService = $this->container->get('PoiService');

        if (!$poiService->checkDataArray($data, false)) {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'Every fields must be filled');
        }

        $poiManager = $em->getRepository('AppBundle:Poi');
        $poi = $poiManager->find($poiId);

        if (null != $poi) {
            $poi = $poiService->updatePoiFromArray($poi, $raid->getId(), $data);
            $em->flush();
        } else {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'This poi does not exist');
        }

        return new Response($poiService->poiToJson($poi));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Delete("/api/organizer/raid/{raidId}/poi/{poiId}")
     *
     * @param Request $request
     * @param int     $raidId  raid id
     * @param int     $poiId   poi id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deletePOIAction(Request $request, $raidId, $poiId)
    {
        // Set up managers
        $em = $this->getDoctrine()->getManager();

        $raidManager = $em->getRepository('AppBundle:Raid');

        // Find the user
        $user = $this->get('security.token_storage')->getToken()->getUser();
        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(['uniqid' => $raidId]);

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'This raid does not exist');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            throw $this->createAccessDeniedException();
        }

        $poiManager = $em->getRepository('AppBundle:Poi');
        $poi = $poiManager->find($poiId);

        if (null != $poi) {
            $em->remove($poi);
            $em->flush();
        } else {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'This poi does not exist');
        }

        return parent::buildJSONStatus(Response::HTTP_OK, 'Deleted');
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Get("/api/organizer/raid/{raidId}/poi/{poiId}")
     *
     * @param Request $request
     * @param int     $raidId  raid id
     * @param int     $poiId   poi id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPoiAction(Request $request, $raidId, $poiId)
    {
        // Set up managers
        $em = $this->getDoctrine()->getManager();

        $raidManager = $em->getRepository('AppBundle:Raid');

        // Find the user
        $user = $this->get('security.token_storage')->getToken()->getUser();
        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(['uniqid' => $raidId]);

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'This raid does not exist');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            throw $this->createAccessDeniedException();
        }

        $poiService = $this->container->get('PoiService');
        $poiManager = $em->getRepository('AppBundle:Poi');
        $poi = $poiManager->find($poiId);

        if (null != $poi) {
            return new Response($poiService->poiToJson($poi));
        } else {
            return parent::buildJSONStatus(Response::HTTP_BAD_REQUEST, 'This poi does not exist');
        }
    }
}
