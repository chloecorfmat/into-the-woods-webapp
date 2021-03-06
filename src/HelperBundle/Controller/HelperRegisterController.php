<?php

namespace HelperBundle\Controller;

use AppBundle\Entity\Helper;
use AppBundle\Entity\PoiType;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class HelperRegisterController extends Controller
{
    /**
     * @Route("/helper/invite/{id}", name="inviteHelper")
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\Response|template
     */
    public function inviteHelper(Request $request, $id)
    {
        // Logout user if one user is login.
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            // authenticated (NON anonymous)
            $this->get('session')->invalidate();
            $anonToken = new AnonymousToken('theTokensKey', 'anon.', array());
            $this->get('security.token_storage')->setToken($anonToken);
        }

        $em = $this->getDoctrine()->getManager();
        $raidManager = $em->getRepository('AppBundle:Raid');
        //$raid = $raidManager->find($id);
        $raid = $raidManager->findOneBy(['uniqid' => $id]);

        if (null === $raid) {
            throw $this->createNotFoundException('Ce raid n\'existe pas');
        }

        $host = ($request->server->get('HTTP_X_FORWARDED_HOST')) ?
            $request->getScheme() . '://' . $request->server->get('HTTP_X_FORWARDED_HOST') :
            $request->getScheme() . '://' . $request->server->get('HTTP_HOST');

        $meta['url'] = $host . $request->server->get('BASE') . $request->getPathInfo();
        $meta['title'] = 'Helper | Raidy';
        $meta['image'] = '/uploads/raids/' . $raid->getPicture();
        $meta['description'] = 'Rejoindre le raid "' . $raid->getName() . '"';

        return $this->render('HelperBundle:Register:inviteHelper.html.twig', [
            'raid' => $raid,
            'meta' => $meta,
            'via' => $this->container->getParameter('app.twitter.account'),
        ]);
    }

    /**
     * @Route("/helper/invite/success/{id}", name="registerSuccessHelper")
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\Response|template
     */
    public function registerSuccessHelper(Request $request, $id)
    {
        // Logout user if one user is login.
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            // authenticated (NON anonymous)
            $this->get('session')->invalidate();
            $anonToken = new AnonymousToken('theTokensKey', 'anon.', array());
            $this->get('security.token_storage')->setToken($anonToken);
        }

        $em = $this->getDoctrine()->getManager();
        $raidManager = $em->getRepository('AppBundle:Raid');
        //$raid = $raidManager->find($id);
        $raid = $raidManager->findOneBy(['uniqid' => $id]);

        if (null === $raid) {
            throw $this->createNotFoundException('Ce raid n\'existe pas');
        }

        $host = ($request->server->get('HTTP_X_FORWARDED_HOST')) ?
            $request->getScheme() . '://' . $request->server->get('HTTP_X_FORWARDED_HOST') :
            $request->getScheme() . '://' . $request->server->get('HTTP_HOST');

        $meta['url'] = $host . $request->server->get('BASE') . $request->getPathInfo();
        $meta['title'] = 'Helper | Raidy';
        $meta['image'] = '/uploads/raids/' . $raid->getPicture();
        $meta['description'] = 'Rejoindre le raid "' . $raid->getName() . '"';

        return $this->render('HelperBundle:Register:registerSuccessHelper.html.twig', [
            'raid' => $raid,
            'meta' => $meta,
            'via' => $this->container->getParameter('app.twitter.account'),
        ]);
    }

    /**
     * @Route("/helper/register/{id}", name="registerHelper")
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\Response|template
     *
     * @throws \Exception
     */
    public function registerHelper(Request $request, $id)
    {
        $host = ($request->server->get('HTTP_X_FORWARDED_HOST')) ?
            $request->getScheme() . '://' . $request->server->get('HTTP_X_FORWARDED_HOST') :
            $request->getScheme() . '://' . $request->server->get('HTTP_HOST');

        // Logout user if one user is login.
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            // authenticated (NON anonymous)
            $this->get('session')->invalidate();
            $anonToken = new AnonymousToken('theTokensKey', 'anon.', array());
            $this->get('security.token_storage')->setToken($anonToken);
        }

        $em = $this->getDoctrine()->getManager();

        $raidManager = $em->getRepository('AppBundle:Raid');
        //$raid = $raidManager->find($id);
        $raid = $raidManager->findOneBy(['uniqid' => $id]);

        $poiTypeManager = $em->getRepository('AppBundle:PoiType');

        $poiTypes = $poiTypeManager->findBy(
            [
            'user' => $raid->getUser(),
            ]
        );

        $choices = [];
        foreach ($poiTypes as $poiType) {
            $choices[$poiType->getType()] = $poiType->getId();
        }

        if (null === $raid) {
            throw $this->createNotFoundException('Ce raid n\'existe pas');
        }

        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('phone', TelType::class, ['label' => 'Numéro de téléphone'])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail'])
            //->add('plainPassword', PasswordType::class, ['label' => 'Mot de passe'])
            //->add('repeatPassword', PasswordType::class, ['label' => 'Répéter le mot de passe'])
            ->add(
                'plainPassword',
                RepeatedType::class,
                array(
                    'error_bubbling' => true,
                    'validation_groups' => ['changePassword'],
                    'type' => PasswordType::class,
                    'invalid_message' => 'Les mots de passe doivent être identiques.',
                    'options' => array('attr' => array('class' => 'password-field')),
                    'required' => true,
                    'first_options' => array('label' => 'Mot de passe'),
                    'second_options' => array('label' => 'Répétez le mot de passe'),
                )
            )
            ->add(
                'poitype',
                ChoiceType::class,
                [
                    'label' => 'Type de poste souhaité pour le bénévolat',
                    'choices' => $choices,
                ]
            )
            ->add(
                'acceptConditions',
                CheckboxType::class,
                [
                    'label' => 'Accepter les conditions',
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'S\'inscrire',
                    'attr' => array('class' => 'btn'),
                ]
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $formatService = $this->container->get('FormatService');

            if ($formData['acceptConditions']) {
                $userManager = $this->get('fos_user.user_manager');
                $phone = $formatService->mobilePhoneNumber($formData['phone']);
                if (!is_null($phone) && 10 === strlen($phone)) {
                    $emailExist = $userManager->findUserByEmail($formData['email']);

                    //if ($formData['plainPassword'] == $formData['repeatPassword']) {
                    if (!$emailExist) {
                        if ($formatService->checkPassword($formData['plainPassword'], $form)) {
                            $user = $userManager->createUser();
                            $user->setUsername($formData['email']);
                            $user->setLastName($formData['lastName']);
                            $user->setFirstName($formData['firstName']);
                            $user->setPhone($phone);
                            $user->setEmail($formData['email']);
                            $user->setEmailCanonical($formData['email']);
                            $user->setEnabled(1);
                            $user->setPlainPassword($formData['plainPassword']);
                            $user->addRole('ROLE_HELPER');

                            $userManager->updateUser($user);

                            // Connect the user manually
                            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                            $this->get('security.token_storage')->setToken($token);

                            $this->get('session')->set('_security_main', serialize($token));

                            $event = new InteractiveLoginEvent($request, $token);
                            $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

                            $helperManager = $em->getRepository('AppBundle:Helper');
                            $alreadyRegistered = $helperManager->findBy(['raid' => $raid, 'user' => $user]);

                            if ($alreadyRegistered) {
                                return $this->redirectToRoute('helper');
                            } else {
                                $poitype = $em->getRepository('AppBundle:PoiType')->find($formData['poitype']);

                                $helper = new Helper();
                                $helper->setRaid($raid);
                                $helper->setFavoritePoiType($poitype);
                                $helper->setUser($user);
                                $helper->setIsCheckedIn(false);
                                $helper->setAcceptConditions(new \DateTime('now'));

                                $em->persist($helper);
                                $em->flush();

                                /* Send email to helper */
                                $message = \Swift_Message::newInstance()
                                    ->setSubject('Création d\'un compte bénévole')
                                    ->setFrom($this->container->getParameter('app.mail.from'))
                                    ->setReplyTo($this->container->getParameter('app.mail.reply_to'))
                                    ->setTo($user->getEmail())
                                    ->setBody(
                                        $this->renderView(
                                            'HelperBundle:Emails:registration.html.twig',
                                            array('user' => $user, 'host' => $host)
                                        ),
                                        'text/html'
                                    );

                                $this->get('mailer')->send($message);

                                /* Send email to organizer */
                                $message = \Swift_Message::newInstance()
                                    ->setSubject('Enregistrement d\'un bénévole pour ' . $raid->getName())
                                    ->setFrom($this->container->getParameter('app.mail.from'))
                                    ->setReplyTo($this->container->getParameter('app.mail.reply_to'))
                                    ->setTo($raid->getUser()->getEmail())
                                    ->setBody(
                                        $this->renderView(
                                            'HelperBundle:Emails:newHelper.html.twig',
                                            array(
                                                'helper' => $user,
                                                'organizer' => $raid->getUser(),
                                                'raid' => $raid,
                                                'host' => $host,
                                            )
                                        ),
                                        'text/html'
                                    );

                                $this->get('mailer')->send($message);

                                return $this->redirectToRoute('registerSuccessHelper', ['id' => $id]);
                            }
                        }
                    } else {
                        $form->addError(
                            new FormError('Un utilisateur avec cette adresse email est déjà enregistré')
                        );
                    }
                } else {
                    $form->addError(
                        new FormError(
                            'Le numéro de téléphone d\'un bénévole doit ' .
                            'être un mobile et ' .
                            'commencer par 06 ou 07. Il comporte 10 numéros.'
                        )
                    );
                }
            } else {
                $form->addError(
                    new FormError(
                        'Vous devez accepter les conditions.'
                    )
                );
            }
        }

        return $this->render(
            'HelperBundle:Register:registerHelper.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/helper/join/{id}", name="joinHelper")
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function joinHelper(Request $request, $id)
    {
        // Logout user if one user is login.
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            // authenticated (NON anonymous)
            $this->get('session')->invalidate();
            $anonToken = new AnonymousToken('theTokensKey', 'anon.', array());
            $this->get('security.token_storage')->setToken($anonToken);
        }

        $em = $this->getDoctrine()->getManager();

        $raidManager = $em->getRepository('AppBundle:Raid');
        //$raid = $raidManager->find($id);
        $raid = $raidManager->findOneBy(['uniqid' => $id]);

        if (null === $raid) {
            throw $this->createNotFoundException('Ce raid n\'existe pas');
        }

        $poiTypeManager = $em->getRepository('AppBundle:PoiType');

        $poiTypes = $poiTypeManager->findBy(
            [
            'user' => $raid->getUser(),
            ]
        );

        $choices = [];
        foreach ($poiTypes as $poiType) {
            $choices[$poiType->getType()] = $poiType->getId();
        }

        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->add('email', TextType::class, ['label' => 'Email'])
            ->add('password', PasswordType::class, ['label' => 'Mot de passe'])
            //->add('poitype', TextType::class, ['label' => 'Type de poste']) // @todo : Use list instead of raw data
            ->add(
                'poitype',
                ChoiceType::class,
                [
                    'label' => 'Type de poste souhaité pour le bénévolat',
                    'choices' => $choices,
                ]
            )
            ->add(
                'acceptConditions',
                CheckboxType::class,
                [
                    'label' => 'Accepter les conditions',
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Se connecter', 'attr' => array('class' => 'btn')])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            if ($this->areNotEmpty($formData)) {
                $userManager = $this->get('fos_user.user_manager');
                $user = $userManager->findUserByEmail($formData['email']);

                //Reject Organizer accounts
                if (!$user) {
                    $form->addError(new FormError('Identifiants invalides'));
                } else {
                    $form->addError(new FormError('Un compte organisateur existe déjà avec cette adresse email'));

                    $encoder = $this->get('security.password_encoder');
                    $isPasswordValid = $encoder->isPasswordValid($user, $formData['password']);

                    if (!$isPasswordValid) { // Le mot de passe n'est pas correct
                        $form->addError(new FormError('Identifiants invalides'));
                    } else {
                        if (!$user->hasRole('ROLE_HELPER')) {
                            $user->addRole('ROLE_HELPER');
                            $em->flush();
                        }

                        // Connect the user manually
                        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                        $this->get('security.token_storage')->setToken($token);

                        $this->get('session')->set('_security_main', serialize($token));

                        $event = new InteractiveLoginEvent($request, $token);
                        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

                        $helperManager = $em->getRepository('AppBundle:Helper');
                        $alreadyRegistered = $helperManager->findBy(['raid' => $raid, 'user' => $user]);

                        if ($alreadyRegistered) {
                            return $this->redirectToRoute('helper');
                        } else {
                            if ($formData['acceptConditions']) {
                                $poitype = $em->getRepository('AppBundle:PoiType')->find($formData['poitype']);

                                $helper = new Helper();
                                $helper->setRaid($raid);
                                $helper->setFavoritePoiType($poitype);
                                $helper->setUser($user);
                                $helper->setIsCheckedIn(false);
                                $helper->setAcceptConditions(new \DateTime('now'));

                                $em->persist($helper);
                                $em->flush();

                                return $this->redirectToRoute('registerSuccessHelper', ['id' => $id]);
                            } else {
                                $form->addError(
                                    new FormError(
                                        'Vous devez accepter les conditions.'
                                    )
                                );
                            }
                        }
                    }
                }
            } else {
                $form->addError(new FormError('Tous les champs doivent être remplis.'));
            }
        }

        $host = ($request->server->get('HTTP_X_FORWARDED_HOST')) ?
            $request->getScheme() . '://' . $request->server->get('HTTP_X_FORWARDED_HOST') :
            $request->getScheme() . '://' . $request->server->get('HTTP_HOST');

        $meta['url'] = $host . $request->server->get('BASE') . $request->getPathInfo();
        $meta['title'] = 'Helper | Raidy';
        $meta['image'] = '/uploads/raids/' . $raid->getPicture();
        $meta['description'] = 'Rejoindre le raid "' . $raid->getName() . '"';

        return $this->render('HelperBundle:Register:joinHelper.html.twig', [
            'form' => $form->createView(),
            'raid' => $raid,
            'via' => $this->container->getParameter('app.twitter.account'),
            'meta' => $meta,
        ]);
    }

    /**
     * @Route("/helper", name="listRaidHelper")
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
        $raids = $raidManager->findBy(
            [
            'user' => $user,
            ]
        );

        return $this->render(
            'OrganizerBundle:Raid:listRaid.html.twig',
            [
                'raids' => $raids,
                'user' => $user,
            ]
        );
    }

    /**
     * @param mixed $formdata data from form
     *
     * @return bool
     */
    private function areNotEmpty($formdata)
    {
        foreach ($formdata as $field) {
            if (null == $field) {
                return false;
            }
        }

        return true;
    }
}
