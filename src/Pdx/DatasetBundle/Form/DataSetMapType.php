<?php

namespace Pdx\DatasetBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DataSetMapType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // pass in the file we need to fetch the columns for
        $data = $builder->getData();
        $csvColumns = array_flip($data['columns']);

        $builder
            ->add('building', ChoiceType::class, [
                'label'    => 'Kolom met HG-identifiers van panden',
                'choices'  => $csvColumns,
                'required' => false,
            ])
//            ->add('street', ChoiceType::class, [
//                'label'    => 'Kolom met HG-identifiers van straten',
//                'choices'  => $csvColumns,
//                'required' => false,
//            ])
            ->add('borough', ChoiceType::class, [
                'label'    => 'Kolom met HG-Identifiers van bonnen',
                'choices'  => $csvColumns,
                'required' => false,
            ])
            ->add('neighbourhood', ChoiceType::class, [
                'label'    => 'Kolom met HG-Identifiers van wijken',
                'choices'  => $csvColumns,
                'required' => false,
            ])

            ->add('numeric', ChoiceType::class, [
                'label'    => 'Selecteer hier de kolommen waarmee gerekend kan worden (numerieke waarden)',
                'choices'  => $csvColumns,
                'required' => false,
                'multiple' => true
            ])
            ->add('strings', ChoiceType::class, [
                'label'    => 'Selecteer hier de kolommen waarmee gefilterd kan worden',
                'choices'  => $csvColumns,
                'required' => false,
                'multiple' => true
            ])
        ;


//        $builder->addEventListener(
//            FormEvents::PRE_SET_DATA,
//            function (FormEvent $event)  {
//                $data = $event->getData();
//
//                $data['file'];
//                //$form = $event->getForm();
//
//                //$csvColumns = $this->csvHandler->getColumns($dataset->getCsvName());
//
//            }
//        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            //'data_class'         => '\Pdx\DatasetBundle\Entity\DataSet',
            'csrf_protection'    => true,
            'allow_extra_fields' => true
        ));

    }

    public function getName()
    {
        return 'pdx_DataSet_bundle_DataSet_type';
    }
}
