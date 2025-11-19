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

            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed',
                    'Cancelled' => 'cancelled',
                ],
            ])

            ->add('notes', TextareaType::class, [
                'required' => false,
            ])

            ->add('client', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $u) {
    return $u->getFirstName() . ' ' . $u->getLastName();
}

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
        ]);
    }
}
