<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Therapist;
use App\Entity\User;
use App\Repository\AppointmentRepository;
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

    // Inject AppointmentRepository to check overlapping appointments
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
                'disabled' => !$options['is_admin'], // only editable if admin
            ])
            ->add('therapist', EntityType::class, [
                'class' => Therapist::class,
                'choice_label' => 'name',
            ]);

        // Add event listener to filter available therapists dynamically
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $appointment = $event->getData();

            if (!$appointment) {
                return;
            }

            $startAt = $appointment->getStartAt();
            $therapistsQuery = $options['is_admin']
                ? null // admin sees all therapists
                : function () use ($startAt) {
                    return $this->appointmentRepository->createQueryBuilder('a')
                        ->select('t')
                        ->from(Therapist::class, 't')
                        ->leftJoin('t.appointments', 'ap')
                        ->where('ap.startAt IS NULL OR NOT (ap.startAt < :newEnd AND DATE_ADD(ap.startAt, 1, \'hour\') > :newStart)')
                        ->setParameter('newStart', $startAt)
                        ->setParameter('newEnd', (clone $startAt)->modify('+1 hour'))
                        ->getQuery();
                };

            if ($therapistsQuery && $startAt) {
                $form->add('therapist', EntityType::class, [
                    'class' => Therapist::class,
                    'choice_label' => 'name',
                    'query_builder' => $therapistsQuery,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'is_admin' => false, // default false
        ]);
    }
}
