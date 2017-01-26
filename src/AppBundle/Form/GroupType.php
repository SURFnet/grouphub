<?php

namespace AppBundle\Form;

use AppBundle\Manager\GroupManager;
use AppBundle\Model\Group;
use RuntimeException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class GroupType
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GroupType extends AbstractType
{
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param AuthorizationChecker  $authorizationChecker
     * @param GroupManager          $groupManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        AuthorizationChecker $authorizationChecker,
        GroupManager $groupManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->groupManager = $groupManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, ['required' => false]);

        $authorizationChecker = $this->authorizationChecker;
        $groupManager = $this->groupManager;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($authorizationChecker, $groupManager) {
                if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
                    return;
                }

                $form = $event->getForm();
                $group = $event->getData();

                $parent = null;
                if (!empty($group)) {
                    $parentId = $group->getParentId();

                    if (!empty($parentId)) {
                        $parent = $groupManager->getGroup($parentId);
                    }
                }

                $user = $this->tokenStorage->getToken()->getUser();

                $form->add(
                    'parent',
                    ChoiceType::class,
                    [
                        'choices'      => $groupManager->findAdminGroups($user->getId()), // @todo: pagination
                        'mapped'       => false,
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'required'     => false,
                        'placeholder'  => 'Ad hoc',
                        'data'         => $parent
                    ]
                );
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var Group $group */
                $group = $event->getData();
                $form = $event->getForm();

                if (empty($group) || !$form->has('parent')) {
                    return;
                }

                if (!empty($form->get('parent')->getData())) {
                    $group->setType(Group::TYPE_SEMI_FORMAL);
                    $group->setParentId($form->get('parent')->getData()->getId());
                } else {
                    $group->setType(Group::TYPE_AD_HOC);
                    $group->setParentId(null);
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new RuntimeException('User should be logged in');
        }

        $resolver->setDefaults(
            [
                'data_class' => Group::class,
                'empty_data' => function (FormInterface $form) use ($user) {
                    $type = Group::TYPE_AD_HOC;
                    $parent = null;

                    if ($form->has('parent') && !empty($form->get('parent')->getData())) {
                        $type = Group::TYPE_SEMI_FORMAL;
                        $parent = $form->get('parent')->getData()->getId();
                    }

                    return new Group(
                        null,
                        '',
                        $form->get('name')->getData(),
                        $form->get('description')->getData(),
                        $type,
                        $user,
                        $parent
                    );
                },
            ]
        );
    }
}
