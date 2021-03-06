<?php

namespace AppBundle\Service;

use AppBundle\Entity\Competitor;
use AppBundle\Entity\Raid;
use Doctrine\ORM\EntityManagerInterface;

class CompetitorService
{
    /**
     * CompetitorService constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Competitor $competitor
     *
     * @return false|string
     */
    public function competitorToJson($competitor)
    {
        $obj = [];

        $obj['id'] = $competitor->getId();
        $obj['competitor1'] = $competitor->getCompetitor1();
        $obj['competitor2'] = $competitor->getCompetitor2();
        $obj['number_sign'] = $competitor->getNumberSign();
        $obj['nfc_serial_id'] = $competitor->getNFCSerialId();
        $obj['category'] = $competitor->getCategory();
        $obj['sex'] = $competitor->getSex();
        $obj['birthyear'] = $competitor->getBirthYear();
        $obj['race'] = $competitor->getRace()->getId();

        $obj['raid'] = $competitor->getRaid()->getId();

        return json_encode($obj);
    }

    /**
     * @param mixed $obj
     * @param mixed $raidId
     *
     * @return Competitor
     */
    public function competitorFromForm($obj, $raidId)
    {
        $competitor = new Competitor();

        $competitor->setCompetitor1($obj->getCompetitor1());
        $competitor->setCompetitor2($obj->getCompetitor2());
        $competitor->setNumberSign($obj->getNumberSign());
        $competitor->setCategory($obj->getCategory());
        $competitor->setSex($obj->getSex());
        $competitor->setBirthYear($obj->getBirthYear());
        $competitor->setRace($obj->getRace());

        $raidRepository = $this->em->getRepository('AppBundle:Raid');
        $raid = $raidRepository->find($raidId);
        $competitor->setRaid($raid);

        $competitor->setUniqid(uniqid());

        return $competitor;
    }

    /**
     * @param array $array
     * @param mixed $raidId
     *
     * @return Competitor
     */
    public function competitorFromCsv($array, $raidId)
    {
        $competitor = new Competitor();
        $competitor->setCompetitor1($array[0]);
        $competitor->setCompetitor2($array[1]);
        $competitor->setNumberSign($array[2]);
        $competitor->setCategory($array[3]);
        $competitor->setSex($array[4]);
        $competitor->setBirthYear($array[5]);
        $competitor->setRace($array[6]);

        $raidRepository = $this->em->getRepository('AppBundle:Raid');
        $raid = $raidRepository->find($raidId);
        $competitor->setRaid($raid);

        $competitor->setUniqid(uniqid());

        return $competitor;
    }

    /**
     * @param array $competitors
     *
     * @return false|string
     */
    public function competitorsArrayToJson($competitors)
    {
        $competitorsObj = [];

        foreach ($competitors as $competitor) {
            $obj = [];

            $obj['id'] = $competitor->getUniqid();
            $obj['competitor1'] = $competitor->getCompetitor1();
            $obj['competitor2'] = $competitor->getCompetitor2();
            $obj['number_sign'] = $competitor->getNumberSign();
            $obj['nfc_serial_id'] = $competitor->getNFCSerialId();
            $obj['category'] = $competitor->getCategory();
            $obj['sex'] = $competitor->getSex();
            $obj['birthyear'] = $competitor->getBirthYear();
            if ($competitor->getRace() != null) {
                $obj['race'] = [];
                $obj['race']['id'] = $competitor->getRace()->getId();
                $obj['race']['name'] = $competitor->getRace()->getName();
            } else {
                $obj['race'] = null;
            }

            $obj['raid'] = $competitor->getRaid()->getId();

            $competitorsObj[] = $obj;
        }

        return json_encode($competitorsObj);
    }

    /**
     * @param mixed $data
     * @return bool
     */
    public function checkRaceTimingData($data)
    {

        if (!isset($data['NFCSerialId']) || $data['NFCSerialId'] == null) {
            return false;
        }

        if (!isset($data['time']) || $data['time'] == null) {
            return false;
        }

        if (!isset($data['poi_id']) || $data['poi_id'] == null) {
            return false;
        }

        return true;
    }
}
