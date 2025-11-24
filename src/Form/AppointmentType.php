<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Therapist;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\TherapistRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AppointmentType extends AbstractType
{
    private AppointmentRepository $appointmentRepository;

    public function __construct(AppointmentRepository $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

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
                'disabled' => !$options['is_admin'],
            ])
            ->add('therapist', EntityType::class, [
                'class' => Therapist::class,
                'choice_label' => 'name',
            ]);

        //
        //
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $appointment = $event->getData();

            if (!$appointment) {
                return;
            }

            $startAt = $appointment->getStartAt();

            // Admin sees all therapists → no restriction
            if ($options['is_admin'] || !$startAt) {
                return; // keep default field
            }
            // User → show only available therapists at selected time

            $form->add('therapist', EntityType::class, [
                'class' => Therapist::class,
                'choice_label' => 'name',
                'query_builder' => function (TherapistRepository $repo) use ($startAt) {
                    $qb = $repo->createQueryBuilder('t');

                    $qb->leftJoin('t.appointments', 'ap')
                        ->andWhere('ap.id IS NULL OR NOT (ap.startAt < :newEnd AND DATE_ADD(ap.startAt, 1, \'hour\') > :newStart)')
                        ->setParameter('newStart', $startAt)
                        ->setParameter('newEnd', (clone $startAt)->modify('+1 hour'));

                    return $qb; // ✔ MUST return QueryBuilder
                },
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'is_admin' => false,
        ]);
    }
}
