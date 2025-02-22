<?php

namespace MauticPlugin\MauticClearbitBundle\Integration;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ClearbitIntegration extends AbstractIntegration
{
    public function getName()
    {
        return 'Clearbit';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * Return array of key => label elements that will be converted to inputs to
     * obtain from the user.
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        // Do not rename field. clearbit.js depends on it
        return [
            'apikey' => 'mautic.integration.clearbit.apikey',
        ];
    }

    /**
     * @param FormBuilder|Form $builder
     * @param array            $data
     * @param string           $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('keys' === $formArea) {
            $builder->add(
                'auto_update',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.plugin.clearbit.auto_update',
                    'data'  => (isset($data['auto_update'])) ? (bool) $data['auto_update'] : false,
                    'attr'  => [
                        'tooltip' => 'mautic.plugin.clearbit.auto_update.tooltip',
                    ],
                ]
            );
        }
    }

    public function shouldAutoUpdate()
    {
        $featureSettings = $this->getKeys();

        return (isset($featureSettings['auto_update'])) ? (bool) $featureSettings['auto_update'] : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string|array
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return [
                'template'   => '@MauticClearbit/Integration/form.html.twig',
                'parameters' => [
                    'mauticUrl' => $this->router->generate(
                        'mautic_plugin_clearbit_index', [], UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ],
            ];
        }

        return parent::getFormNotes($section);
    }
}
