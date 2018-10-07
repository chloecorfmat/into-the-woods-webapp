<?php

namespace OrganizerBundle\Controller;

use AppBundle\Entity\Raid;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\File;

class OrganizerRaidController extends Controller
{
    /**
     * @Route("/raid/new", name="addRaid")
     *
     * @param Request $request request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addRaid(Request $request)
    {
        $formRaid = new Raid();

        $form = $this->createFormBuilder($formRaid)
            ->add('name', TextType::class, array('label' => 'Nom du raid'))
            ->add('date', DateType::class, array(
                'label' => 'Date',
                'widget' => 'single_text',
                'html5' => true,
            ))
            ->add('address', TextType::class, array('label' => 'Adresse'))
            ->add('addressAddition', TextType::class, array('required' => false, 'label' => 'Complément d\'adresse'))
            ->add('postCode', IntegerType::class, array('label' => 'Code postal'))
            ->add('city', TextType::class, array('label' => 'Ville'))
            ->add('editionNumber', IntegerType::class, array('label' => 'Numéro d\'édition'))
            ->add('picture', FileType::class, array(
                'label' => 'Photo',
                'label_attr' => array('class' => 'form--fixed-label'),
                'required' => false,
                'data_class' => null,
            ))
            ->add('submit', SubmitType::class, array('label' => 'Créer un raid'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $raidManager = $em->getRepository('AppBundle:Raid');
            $raidExist = $raidManager->findBy(
                array('name' => $formRaid->getName(), 'editionNumber' => $formRaid->getEditionNumber())
            );
            if (!$raidExist) {
                $formRaid = $form->getData();

                $fileName = $this->saveFile($formRaid->getPicture());

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

                return $this->redirectToRoute('raidList');
            } else {
                $form->addError(new FormError('Ce raid existe déjà.'));
            }
        }

        return $this->render('OrganizerBundle:Raid:addRaid.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/raid/{id}", name="displayRaid")
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

        $em = $this->getDoctrine()->getManager();
        $raidManager = $em->getRepository('AppBundle:Raid');

        $formRaid = $raidManager->findOneBy(['id' => $id]);

        if (null == $formRaid) {
            throw $this->createNotFoundException('Ce raid n\'existe pas');
        }

        $form = $this->createFormBuilder($formRaid)
            ->add('name', TextType::class, array('label' => 'Nom du raid'))
            ->add('date', DateType::class, array(
                'label' => 'Date',
                'widget' => 'single_text',

                // prevents rendering it as type="date", to avoid HTML5 date pickers
                'html5' => true,
            ))
            ->add('address', TextType::class, array('label' => 'Adresse'))
            ->add('addressAddition', TextType::class, array('required' => false, 'label' => 'Complément d\'adresse'))
            ->add('postCode', IntegerType::class, array('label' => 'Code postal'))
            ->add('city', TextType::class, array('label' => 'Ville'))
            ->add('editionNumber', IntegerType::class, array('label' => 'Numéro d\'édition'))
            ->add('picture', FileType::class, array(
                'label_attr' => array('class' => 'form--fixed-label'),
                'label' => 'Photo',
                'required' => false,
                'data_class' => null,
            ))
            ->add('submit', SubmitType::class, array('label' => 'Editer un raid'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $raidExist = $raidManager->findOneBy(
                array('name' => $formRaid->getName(), 'editionNumber' => $formRaid->getEditionNumber())
            );

            if (!$raidExist || $raidExist->getId() == $formRaid->getId()) {
                $formRaid = $form->getData();

                $raid = $raidManager->findOneBy(array('id' => $formRaid->getId()));

                $raid->setName($formRaid->getName());
                $raid->setDate($formRaid->getDate());
                $raid->setAddress($formRaid->getAddress());
                if ($formRaid->getAddressAddition()) {
                    $raid->setAddressAddition($formRaid->getAddressAddition());
                }
                $raid->setPostCode($formRaid->getPostCode());
                $raid->setCity($formRaid->getCity());
                $raid->setEditionNumber($formRaid->getEditionNumber());
                if (null != $formRaid->getPicture()) {
                    $fileName = $this->saveFile($formRaid->getPicture());
                    $raid->setPicture($fileName);
                } else {
                    $raid->setPicture(
                        new File($this->getParameter('raids_img_directory') . '/' . $raid->getPicture())
                    );
                }

                $em->persist($raid);
                $em->flush();
            }
        }

        return $this->render('OrganizerBundle:Raid:raid.html.twig', [
            'form' => $form->createView(),
            'raid' => $raid,
        ]);
    }

    /**
     * @Route("/raid/delete/{id}", name="deleteRaid")
     *
     * @param Request $request request
     * @param mixed   $id      id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteRaid(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $raidManager = $em
            ->getRepository('AppBundle:Raid');
        $raid = $raidManager->findOneBy(array('id' => $id));
        if (null == $raid) {
            throw $this->createNotFoundException('Ce raid n\'existe pas');
        }

        $em->remove($raid);
        $em->flush();

        return $this->redirectToRoute('raidList');
    }

    /**
     * @Route("/raid", name="raidList")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listRaids()
    {
        $raidManager = $this->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Raid');

        $raids = $raidManager->findAll();

        return $this->render(
            'OrganizerBundle:Raid:listRaid.html.twig',
            [
                'raids' => $raids,
            ]
        );
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

    /**
     * @param mixed $file the file to save
     * @return string
     */
    private function saveFile($file)
    {
        // $file stores the uploaded file
        /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
        $fileName = $this->generateUniqueFileName() . '.' . $file->guessExtension();

        // Move the file to the directory where brochures are stored
        try {
            $file->move(
                $this->getParameter('raids_img_directory'),
                $fileName
            );
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return $fileName;
    }
}