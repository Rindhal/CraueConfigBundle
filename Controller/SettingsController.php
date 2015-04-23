<?php

namespace Craue\ConfigBundle\Controller;

use Craue\ConfigBundle\Entity\SettingInterface;
use Craue\ConfigBundle\Form\ModifySettingsForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Christian Raue <christian.raue@gmail.com>
 * @copyright 2011-2015 Christian Raue
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class SettingsController extends Controller {

	public function modifyAction() {
		$em = $this->getDoctrine()->getManager();
		$repo = $em->getRepository($this->container->getParameter('craue_config.setting.class'));
		$allStoredSettings = $repo->findAll();

		$formData = array(
			'settings' => $allStoredSettings,
		);

		$form = $this->createForm(new ModifySettingsForm(), $formData);
		$request = $this->getCurrentRequest();
		if ($request->getMethod() === 'POST') {
			if (Kernel::VERSION_ID < 20300) {
				$form->bind($request);
			} else {
				$form->handleRequest($request);
			}

			if ($form->isValid()) {
				foreach ($formData['settings'] as $formSetting) {
					$storedSetting = $this->getSettingByName($allStoredSettings, $formSetting->getName());
					if ($storedSetting !== null) {
						$storedSetting->setValue($formSetting->getValue());
						$em->persist($storedSetting);
					}
				}

				$em->flush();

				$this->get('session')->getFlashBag()->set('notice',
						$this->get('translator')->trans('settings_changed', array(), 'CraueConfigBundle'));
				return $this->redirect($this->generateUrl($this->container->getParameter('craue_config.redirectRouteAfterModify')));
			}
		}

		return $this->render('CraueConfigBundle:Settings:modify.html.twig', array(
			'form' => $form->createView(),
			'sections' => $this->getSections($allStoredSettings),
		));
	}

	/**
	 * @param SettingInterface[] $settings
	 * @return string[] (may also contain a null value)
	 */
	protected function getSections(array $settings) {
		$sections = array();

		foreach ($settings as $setting) {
			$section = $setting->getSection();
			if (!in_array($section, $sections)) {
				$sections[] = $section;
			}
		}

		sort($sections);

		return $sections;
	}

	/**
	 * @param SettingInterface[] $settings
	 * @param string $name
	 * @return SettingInterface|null
	 */
	protected function getSettingByName(array $settings, $name) {
		foreach ($settings as $setting) {
			if ($setting->getName() === $name) {
				return $setting;
			}
		}
	}

	/**
	 * @return Request
	 */
	protected function getCurrentRequest() {
		if ($this->has('request_stack')) {
			return $this->get('request_stack')->getCurrentRequest();
		}

		// TODO remove as soon as Symfony >= 2.4 is required
		if ($this->has('request')) {
			return $this->get('request');
		}
	}

}
