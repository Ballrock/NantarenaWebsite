<?php

namespace Nantarena\EventBundle\Controller;

use Nantarena\EventBundle\Entity\Entry;
use Nantarena\EventBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class EventController
 *
 * @package Nantarena\EventBundle\Controller
 *
 * @Route("/event")
 */
class EventController extends Controller
{
    /**
     * @Route("/{slug}", name="nantarena_event_show")
     * @Template()
     */
    public function showAction($slug)
    {
        $em = $this->getDoctrine()->getManager();
        $event = $em->getRepository('NantarenaEventBundle:Event')->findOneShow($slug);

        return array(
            'event' => $event,
        );
    }

    /**
     * @Route("/{slug}/reglement", name="nantarena_event_rules")
     */
    public function rulesAction(Event $event)
    {
        return $this->forward('NantarenaSiteBundle:Resource:show', array(
            'resource' => $event->getRules()
        ));
    }

    /**
     * @Route("/{slug}/autorisation", name="nantarena_event_autorization")
     */
    public function autorizationAction(Event $event)
    {
        return $this->forward('NantarenaSiteBundle:Resource:show', array(
            'resource' => $event->getAutorization()
        ));
    }

    /**
     * @Route("/{slug}/participate", name="nantarena_event_participate")
     * @Template()
     */
    public function participateAction(Request $request, $slug)
    {
        $translator = $this->get('translator');
        $flashbag = $this->get('session')->getFlashBag();
        $validator = $this->get('validator');
        $em = $this->get('doctrine.orm.entity_manager');

        $now = new \DateTime();

        /** @var \Nantarena\EventBundle\Entity\Event $event */
        $event = $em->getRepository('NantarenaEventBundle:Event')->findOneShow($slug);

        // Check date constraints
        if ($event->getStartRegistrationDate() > $now)
            $flashbag->add('error', $translator->trans('event.participate.flash.notyet'));

        if ($event->getEndRegistrationDate() <= $now)
            $flashbag->add('error', $translator->trans('event.participate.flash.closed'));

        if ($flashbag->has('error'))
            return $this->redirect($this->generateUrl('nantarena_event_show', array(
                'slug' => $event->getSlug()
            )));

        // Check if user is logged
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $flashbag->add('error', $translator->trans('event.participate.flash.login'));
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $user = $this->get('security.context')->getToken()->getUser();

        // Check if user profile is completed
        $errors = $validator->validate($user, array('identity'));

        if (count($errors) > 0) {
            $flashbag->add('error', $translator->trans('event.participate.flash.profile'));
            return $this->redirect($this->generateUrl('fos_user_profile_edit'));
        }

        // Check if user is not already registered
        if ($user->hasEntry($event)) {
            $flashbag->add('error', $translator->trans('event.participate.flash.already'));
            return $this->redirect($this->generateUrl('nantarena_user_profile'));
        }

        // Check if event is full
        if ($event->isFull()) {
            $flashbag->add('error', $translator->trans('event.participate.flash.full'));
            return $this->redirect($this->generateUrl('nantarena_event_show', array(
                'slug' => $event->getSlug()
            )));
        }

        // Check if user is underage or not
        $diff = $event->getStartDate()->diff($user->getBirthDate());

        // Creating the form
        $form = $this->createFormBuilder();
        
        foreach($event->getEntryTypes() as $type) {
            $form->add('entrytype-'.$type->getId(), 'submit');
        }

        $form = $form
            ->getForm()
            ->handleRequest($request)
        ;

        // Process the registration
        if ($form->isValid()) {

            foreach($event->getEntryTypes() as $type) {
                if ($form->get('entrytype-'.$type->getId())->isClicked()) {

                    $entry = new Entry();
                    $entry->setEntryType($type);
                    $user->addEntry($entry);

                    $em->persist($entry);
                    break;
                }
            }

            $em->flush();

            $flashbag->add('success', $translator->trans('event.participate.flash.success'));
            return $this->redirect($this->generateUrl('nantarena_user_profile'));
        }

        return array(
            'event' => $event,
            'underage' => ($diff->y < 18),
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/{slug}/cancel", name="nantarena_event_cancel")
     * @Template()
     */
    public function cancelAction(Request $request, Event $event)
    {
        $translator = $this->get('translator');
        $flashbag = $this->get('session')->getFlashBag();

        // Check if user is logged
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return new NotFoundHttpException();
        }

        $user = $this->get('security.context')->getToken()->getUser();
        $entry = null;

        // Check if user is already registered
        if (!$user->hasEntry($event, $entry)) {
            $flashbag->add('error', $translator->trans('event.cancel.flash.notyet'));
            return $this->redirect($this->generateUrl('nantarena_user_profile'));
        }

        // TODO: Check if user has not paid

        $form = $this->createDeleteForm($event);
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                if ($form->get('id')->getData() == $event->getId() || null === $entry) {
                    $em = $this->getDoctrine()->getManager();

                    $em->remove($entry);
                    $em->flush();

                    $flashbag->add('success', $translator->trans('event.cancel.flash.success'));
                } else {
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                $flashbag->add('error', $translator->trans('event.cancel.flash.error'));
            }

            return $this->redirect($this->generateUrl('nantarena_user_profile'));
        }

        return array(
            'form' => $form->createView(),
            'event' => $event
        );
    }

    /**
     * Creates a form to delete an Entry entity by id.
     *
     * @param Event $event
     * @return \Symfony\Component\Form\Form
     */
    private function createDeleteForm($event)
    {
        // TODO : Rajouter un champ si le joueur est créateur d'une équipe, pour transférer la propriété
        return $this->createFormBuilder(array('id' => $event->getId()))
            ->add('id', 'hidden')
            ->add('submit', 'submit')
            ->setMethod('POST')
            ->setAction($this->generateUrl('nantarena_event_cancel', array(
                'slug' => $event->getSlug()
            )))
            ->getForm();
    }

    /**
     * @Template()
     */
    public function menuAction()
    {
        $em = $this->getDoctrine()->getManager();
        $event = $em->getRepository('NantarenaEventBundle:Event')->findNext();

        return array(
            'event' => $event,
        );
    }
}
