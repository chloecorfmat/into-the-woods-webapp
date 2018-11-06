<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace OrganizerBundle\Controller;

use AppBundle\Entity\Raid;
use OrganizerBundle\Security\RaidVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class OrganizerRaidController extends Controller
{
    /**
     * @Route("/organizer/raid/add", name="addRaid")
     *
     * @param Request $request request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addRaid(Request $request)
    {
        $formRaid = new Raid();

        $form = $this->createFormBuilder($formRaid)
            ->add('name', TextType::class, ['label' => 'Nom du raid'])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('address', TextType::class, ['label' => 'Adresse'])
            ->add('addressAddition', TextType::class, ['required' => false, 'label' => 'Complément d\'adresse'])
            ->add('postCode', IntegerType::class, ['label' => 'Code postal'])
            ->add('city', TextType::class, ['label' => 'Ville'])
            ->add('editionNumber', IntegerType::class, ['label' => 'Numéro d\'édition'])
            ->add('picture', FileType::class, [
                'label' => 'Photo',
                'label_attr' => ['class' => 'form--fixed-label'],
                'data_class' => null,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Créer un raid'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $raidManager = $em->getRepository('AppBundle:Raid');
            $raidExist = $raidManager->findBy(
                ['name' => $formRaid->getName(), 'editionNumber' => $formRaid->getEditionNumber()]
            );
            if (!$raidExist) {
                $formRaid = $form->getData();

                $uploadedFileService = $this->container->get('UploadedFileService');
                $fileName = $uploadedFileService->saveFile(
                    $formRaid->getPicture(),
                    $this->getParameter('raids_img_directory')
                );

                $raid = new Raid();

                $raid->setName($formRaid->getName());
                $raid->setDate($formRaid->getDate());
                $raid->setAddress($formRaid->getAddress());
                if ($formRaid->getAddressAddition()) {
                    $raid->setAddressAddition($formRaid->getAddressAddition());
                }
                $raid->setPostCode($formRaid->getPostCode());
                $raid->setCity($formRaid->getCity());
                $raid->setEditionNumber($formRaid->getEditionNumber());
                $raid->setUser($this->getUser());
                $raid->setPicture($fileName);

                $em->persist($raid);
                $em->flush();

                return $this->redirectToRoute('listRaid');
            }
            $form->addError(new FormError('Ce raid existe déjà.'));
        }

        return $this->render('OrganizerBundle:Raid:addRaid.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/organizer/raid/{id}", name="displayRaid")
     *
     * @param Request $request request
     * @param int     $id      raid identifier
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayRaid(Request $request, $id)
    {
        $raidManager = $this->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Raid');

        $raid = $raidManager->find($id);

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();
        $raidManager = $em->getRepository('AppBundle:Raid');

        $formRaid = $raidManager->findOneBy(['id' => $id]);

        $oldPicture = $formRaid->getPicture();

        if (null === $formRaid) {
            throw $this->createNotFoundException('Ce raid n\'existe pas');
        }

        $form = $this->createFormBuilder($formRaid)
            ->add('name', TextType::class, ['label' => 'Nom du raid'])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',

                // prevents rendering it as type="date", to avoid HTML5 date pickers
                'html5' => true,
            ])
            ->add('address', TextType::class, ['label' => 'Adresse'])
            ->add('addressAddition', TextType::class, ['required' => false, 'label' => 'Complément d\'adresse'])
            ->add('postCode', IntegerType::class, ['label' => 'Code postal'])
            ->add('city', TextType::class, ['label' => 'Ville'])
            ->add('editionNumber', IntegerType::class, ['label' => 'Numéro d\'édition'])
            ->add('picture', FileType::class, [
                'label_attr' => ['class' => 'form--fixed-label'],
                'label' => 'Photo',
                'required' => false,
                'data_class' => null,
                'attr' => [
                    'data_url' => 'uploads/raids/',
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Editer un raid'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $raidExist = $raidManager->findOneBy(
                ['name' => $formRaid->getName(), 'editionNumber' => $formRaid->getEditionNumber()]
            );

            if (!$raidExist || $raidExist->getId() === $formRaid->getId()) {
                $formRaid = $form->getData();

                $raid = $raidManager->findOneBy(['id' => $formRaid->getId()]);

                $raid->setName($formRaid->getName());
                $raid->setDate($formRaid->getDate());
                $raid->setAddress($formRaid->getAddress());
                if ($formRaid->getAddressAddition()) {
                    $raid->setAddressAddition($formRaid->getAddressAddition());
                }
                $raid->setPostCode($formRaid->getPostCode());
                $raid->setCity($formRaid->getCity());
                $raid->setEditionNumber($formRaid->getEditionNumber());

                if (null !== $formRaid->getPicture()) {
                    $uploadedFileService = $this->container->get('UploadedFileService');
                    $fileName = $uploadedFileService->saveFile(
                        $formRaid->getPicture(),
                        $this->getParameter('raids_img_directory')
                    );
                    $raid->setPicture($fileName);
                } else {
                    $raid->setPicture($oldPicture);
                }

                $em->persist($raid);
                $em->flush();

                $this->addFlash('success', 'Le raid a bien été mis à jour.');
            }
        }

        return $this->render('OrganizerBundle:Raid:raid.html.twig', [
            'form' => $form->createView(),
            'raid' => $raid,
        ]);
    }

    /**
     * @Route("/organizer/raid/delete/{id}", name="deleteRaid")
     *
     * @param Request $request request
     * @param mixed   $id      id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteRaid(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $raidManager = $em->getRepository('AppBundle:Raid');
        $trackManager = $em->getRepository('AppBundle:Track');
        $poiManager = $em->getRepository('AppBundle:Poi');

        $raid = $raidManager->findOneBy(['id' => $id]);

        if (null === $raid) {
            throw $this->createNotFoundException('Ce raid n\'existe pas');
        }

        $authChecker = $this->get('security.authorization_checker');
        if (!$authChecker->isGranted(RaidVoter::EDIT, $raid)) {
            throw $this->createAccessDeniedException();
        }

        $tracks = $trackManager->findBy(array('raid' => $raid->getId()));

        if (null != $tracks) {
            foreach ($tracks as $track) {
                $pois = $poiManager->findBy(array('track' => $track->getId()));
                if (null != $pois) {
                    foreach ($pois as $poi) {
                        $em->remove($poi);
                        $em->flush();
                    }
                }

                $em->remove($track);
                $em->flush();
            }
        }

        $em->remove($raid);
        $em->flush();

        return $this->redirectToRoute('listRaid');
    }

    /**
     * @Route("/organizer", name="listRaid")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listRaids()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $raidManager = $this->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Raid');

        //$raids = $raidManager->findAll();
        $raids = $raidManager->findBy([
            'user' => $user,
        ]);

        return $this->render(
            'OrganizerBundle:Raid:listRaid.html.twig',
            [
                'raids' => $raids,
                'user' => $user,
            ]
        );
    }
}
