<?php

namespace App\Form;

use App\Entity\Therapist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;


class TherapistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('name')
    ->add('specialization')
    ->add('email')
    ->add('phone')
    ->add('description')
    ->add('photo', FileType::class, [
        'label' => 'Photo (JPEG or PNG file)',
        'mapped' => false, // important, not mapped directly to entity
        'required' => false,
        'constraints' => [
            new File([
                'maxSize' => '5M',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                ],
                'mimeTypesMessage' => 'Please upload a valid image (JPEG or PNG)',
            ])
        ],
    ])
;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Therapist::class,
        ]);
    }
}
