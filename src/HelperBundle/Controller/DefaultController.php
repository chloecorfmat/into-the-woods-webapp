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

namespace HelperBundle\Controller;

use AppBundle\Entity\Helper;
use AppBundle\Entity\Raid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/helper", name="helper")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $user = $this->getUser();

        /* @todo refacto with DQL join to remove useless queries */
        $em = $this->get('doctrine.orm.entity_manager');
        $helpers = $em->getRepository('AppBundle:Helper')->findBy([
            'user' => $user,
        ]);

        $raidIds = [];
        foreach ($helpers as $h) {
            $rid = $h->getRaid()->getId();
            $raidIds[] = $rid;
        }

        $raids = $em->getRepository('AppBundle:Raid')->findBy([
            'id' => $raidIds,
        ]);

        return $this->render('HelperBundle:Default:index.html.twig', [
            'raids' => $raids,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/helper/profile/edit", name="editHelperProfile")
     *
     * @param Request $request request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editHelperProfile(Request $request)
    {

        $user = $this->getUser();

        $form = $this->createFormBuilder($user, array('validation_groups' => array('editProfile')))
            ->add('username', TextType::class, ['label' => 'Nom d\'utilisateur'])
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('phone', TelType::class, ['label' => 'Numéro de téléphone'])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail'])
            ->add('submit', SubmitType::class, ['label' => 'Modifier le profil'])
            ->getForm();

        $editPasswordform = $this->createFormBuilder($user, array('validation_groups' => array('changePassword')))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options'  => array('label' => 'Mot de passe'),
                'second_options' => array('label' => 'Répétez le mot de passe'),
            ))
            ->add('submit', SubmitType::class, ['label' => 'Modifier le mot de passe'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);
        }

        if ($editPasswordform->isSubmitted() && $editPasswordform->isValid()) {
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);
        }

        return $this->render('HelperBundle:Profile:editProfile.html.twig', [
            'form' => $form->createView(),
            'editPasswordForm' => $editPasswordform->createView(),
        ]);
    }
}
