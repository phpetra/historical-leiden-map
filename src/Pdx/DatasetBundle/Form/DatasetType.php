<?php

namespace Pdx\DatasetBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * Class DataSetType
 * file field is NOT required
 *
 */
class DataSetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label'       => 'Titel van je Dataset. Geef svp een korte, heldere titel.',
                'attr'        => ['placeholder' => 'Titel'],
                'required'    => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ])
            ->add('period', TextType::class, [
                'label'    => 'Vermeld de jaartallen waarop de data betrekking heeft',
                'attr'     => [
                    'placeholder' => 'Periode',
                ],
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label'       => 'Ruimte voor een uitgebreide beschrijving van je bestand.',
                'attr'        => [
                    'placeholder' => 'Beschrijving',
                    'class'       => 'pdx-textarea'
                ],
                'required'    => false,
            ])
            ->add('csvFile', VichFileType::class, array(
                'required'      => false,
                'allow_delete'  => false, // not mandatory, default is true
                'download_link' => false, // not mandatory, default is true
            ))
            ->add('version', TextType::class, [
                'label'    => 'Versie van het bestand',
                'required' => false,
            ])
            ->add('credits', TextType::class, [
                'label'    => 'Bewerker of maker van de dataset',
                'required' => false,
            ])
            ->add('website', TextType::class, [
                'label'    => 'Link naar de originele bron',
                'required' => false,
                'attr'        => [
                    'placeholder' => 'http://www.website.nl',
                ],
                'constraints' => [
                    new Assert\Url(),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => '\Pdx\DatasetBundle\Entity\DataSet',
            'csrf_protection'    => true,
            'allow_extra_fields' => true
        ));

    }

    public function getName()
    {
        return 'pdx_DataSet_bundle_DataSet_type';
    }
}
