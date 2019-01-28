<?php
/**
 * Created by PhpStorm.
 * User: lucas
 * Date: 19/10/18
 * Time: 11:15.
 */

namespace APIBundle\Controller;

use AppBundle\Controller\AjaxAPIController;
use AppBundle\Entity\Competitor;
use AppBundle\Entity\RaceCheckpoint;
use AppBundle\Entity\RaceTiming;
use DateTime;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompetitorController extends AjaxAPIController
{
    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Get("/api/helper/raid/{raidId}/competitor")
     * @Rest\Get("/api/organizer/raid/{raidId}/competitor")
     *
     * @param Request $request request
     * @param int     $raidId  raid id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCompetitorAction(Request $request, $raidId)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $competitorManager = $em->getRepository('AppBundle:Competitor');
        $raidManager = $em->getRepository('AppBundle:Raid');

        //$raid = $raidManager->findOneBy(array('id' => $raidId));
        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $competitors = $competitorManager->findBy(array('raid' => $raid->getId()));
        $competitorService = $this->container->get('CompetitorService');

        return new Response($competitorService->competitorsArrayToJson($competitors));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Get("/api/helper/raid/{raidId}/race/{raceId}/competitor")
     * @Rest\Get("/api/organizer/raid/{raidId}/race/{raceId}/competitor")
     *
     * @param Request $request request
     * @param int     $raidId  raid id
     * @param int     $raceId  race id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCompetitorByRaceAction(Request $request, $raidId, $raceId)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $competitorManager = $em->getRepository('AppBundle:Competitor');
        $raidManager = $em->getRepository('AppBundle:Raid');
        $raceManager = $em->getRepository('AppBundle:Race');

        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $competitors = $competitorManager->findBy(array('race' => $raceId));

        $competitorService = $this->container->get('CompetitorService');

        return new Response($competitorService->competitorsArrayToJson($competitors));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Get("/api/helper/raid/{raidId}/race/{raceId}/competitor/numbersign/{numberSign}")
     * @Rest\Get("/api/organizer/raid/{raidId}/race/{raceId}/competitor/numbersign/{numberSign}")
     *
     * @param Request $request    request
     * @param int     $raidId     raid id
     * @param int     $raceId     race id
     * @param int     $numberSign Number sign
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCompetitorByNumberSignAction(Request $request, $raidId, $raceId, $numberSign)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $competitorManager = $em->getRepository('AppBundle:Competitor');
        $raidManager = $em->getRepository('AppBundle:Raid');

        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $competitor = $competitorManager->findOneBy(array('race' => $raceId, 'numberSign' => $numberSign));

        $competitorService = $this->container->get('CompetitorService');

        return new Response($competitorService->competitorToJson($competitor));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Get("/api/helper/raid/{raidId}/race/{raceId}/competitor/nfcserialid/{nfcserialid}")
     * @Rest\Get("/api/organizer/raid/{raidId}/race/{raceId}/competitor/nfcserialid/{nfcserialid}")
     *
     * @param Request $request     request
     * @param int     $raidId      raid id
     * @param int     $raceId      race id
     * @param int     $nfcserialid nfc badge serial id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCompetitorByNumberNFCSerialId(Request $request, $raidId, $raceId, $nfcserialid)
    {
        // Get managers
        $em = $this->getDoctrine()->getManager();
        $competitorManager = $em->getRepository('AppBundle:Competitor');
        $raidManager = $em->getRepository('AppBundle:Raid');

        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $competitor = $competitorManager->findOneBy(array('race' => $raceId, 'NFCSerialId' => $nfcserialid));

        $competitorService = $this->container->get('CompetitorService');

        return new Response($competitorService->competitorToJson($competitor));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Patch("/api/helper/raid/{raidId}/race/{raceId}/competitor/{numberSign}")
     * @Rest\Patch("/api/organizer/raid/{raidId}/race/{raceId}/competitor/{numberSign}")
     *
     * @param Request $request    request
     * @param int     $raidId     raid id
     * @param int     $raceId     race id
     * @param int     $numberSign competitor number sign
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setNFCSerialIdAction(Request $request, $raidId, $raceId, $numberSign)
    {
        $data = $request->request->all();
        $NFCSerialId = $data['NFCSerialId'];

        // Get managers
        $em = $this->getDoctrine()->getManager();
        $competitorManager = $em->getRepository('AppBundle:Competitor');
        $raidManager = $em->getRepository('AppBundle:Raid');

        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        /** @var Competitor $competitor */
        $competitor = $competitorManager->findOneBy(array('race' => $raceId, 'numberSign' => $numberSign));

        $competitor->setNFCSerialId($NFCSerialId);
        $em->flush();

        return parent::buildJSONStatus(Response::HTTP_OK, 'Competitor updated');
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Put("/api/helper/raid/{raidId}/racetiming")
     *
     * @param Request $request request
     * @param int     $raidId  raid id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addRaceTimingAction(Request $request, $raidId)
    {
        $data = $request->request->all();
        $NFCSerialId = $data['NFCSerialId'];
        $time = new DateTime($data['time']);
        $checkpointId = $data['checkpoint_id'];

        // Get managers
        $em = $this->getDoctrine()->getManager();

        $raceCheckpointManager = $em->getRepository('AppBundle:RaceCheckpoint');
        /** @var RaceCheckpoint $raceCheckpoint */
        $raceCheckpoint = $raceCheckpointManager->find($checkpointId);

        $competitorManager = $em->getRepository('AppBundle:Competitor');
        /** @var Competitor $competitor */
        $competitor = $competitorManager->findOneBy(["NFCSerialId" => $NFCSerialId]);

        $raidManager = $em->getRepository('AppBundle:Raid');
        $raid = $raidManager->findOneBy(array('uniqid' => $raidId));

        if (null == $raid) {
            return parent::buildJSONStatus(Response::HTTP_NOT_FOUND, 'Ce raid n\'existe pas');
        }

        $raceTiming = new RaceTiming();
        $raceTiming->setCheckpoint($raceCheckpoint);
        $raceTiming->setCompetitor($competitor);
        $raceTiming->setTime($time);

        $em->persist($raceTiming);
        $em->flush();

        return parent::buildJSONStatus(Response::HTTP_OK, 'Competitor updated');
    }
}
