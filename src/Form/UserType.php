<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('username', TextType::class, [
                'label' => "Nom d'utilisateur",
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
            ]);

        if (!$isEdit) {
            $builder->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'label' => 'Mot de passe',
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'required' => true,
                'first_options'  => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmation'],
                'mapped' => false,
            ]);
        }

        $builderData = $builder->getData();

        /** @var User|null $currentUser */
        $currentUser = $options['current_user'];

        if (!$currentUser || !$builderData || $currentUser->getId() !== $builderData->getId()) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'current_user' => null,
            'is_edit' => false,
        ]);
    }
}
