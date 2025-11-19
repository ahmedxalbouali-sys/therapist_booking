<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Therapist;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startAt', DateTimeType::class, [
                'widget' => 'single_text',
            ])

            ->add('notes', TextareaType::class, [
                'required' => false,
            ])

            ->add('client', EntityType::class, [
    'class' => User::class,
    'choice_label' => fn(User $u) => $u->getFirstName() . ' ' . $u->getLastName(),
    'disabled' => !$options['is_admin'], // only editable if admin
])

            ->add('therapist', EntityType::class, [
                'class' => Therapist::class,
                'choice_label' => 'name',   // or firstName / lastName depending on your entity
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'is_admin' => false, // default false
        ]);
    }
}
