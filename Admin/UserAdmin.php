<?php

namespace Canabelle\CMSUserBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Canabelle\CMSUserBundle\Entity\User;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\UserBundle\Admin\Entity\UserAdmin as BaseUserAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserAdmin extends BaseUserAdmin
{
    public static $ROLE_ADMIN = 'ROLE_CANABELLE_CMS_USER_ADMIN_USER_ADMIN';

    protected $baseRoutePattern = 'administrace/uzivatele';

    protected $maxPerPage = 50;

    protected $perPageOptions = array(10, 25, 50, 100, 500, 1000);

    /** @var ContainerInterface */
    protected $container;

    /** @var EntityManager */
    protected $entityManager;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'create', 'edit'));
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $collection->add('delete');
        }
        $collection->add('store_cropped_avatar', 'store-cropped-avatar/{userId}');
        $collection->add('store_property', 'store-property');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $now = new \DateTime();

        $userGroupsQuery = $this->entityManager->getRepository('CanabelleCMSUserBundle:Group')->getGroupsQuery();

        $formMapper
            ->tab('User')
                ->with('Basic', array('class' => 'col-md-6'))
                    ->add('firstname', null, array('required' => false))
                    ->add('lastname', null, array('required' => false))
                    ->add('gender', 'sonata_user_gender', array(
                        'required' => true,
                        'expanded' => true,
                        'translation_domain' => $this->getTranslationDomain()
                    ))
                    ->add('avatar', $this->id($this->getSubject()) ? 'user_avatar' : 'hidden', array('label' => 'User.Avatar', 'required' => false,))
                ->end()
                ->with('Contact', array('class' => 'col-md-6'))
                    ->add('email')
                    ->add('phone', null, array('required' => false))
                    //->add('street', null, array('required' => false))
                    //->add('city', null, array('required' => false))
                    //->add('zip', null, array('required' => false))
                ->end();

                if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                    $formMapper->with('User', array('class' => 'col-md-6'))
                        ->add('username', null, array('required' => true))
                        ->add('plainPassword', 'text', array(
                            'required' => (!$this->getSubject() || is_null($this->getSubject()->getId()))
                        ));
                        if ($this->isAdmin()) {
                            $formMapper->add('groups', 'sonata_type_model', array(
                                'required' => false,
                                'expanded' => true,
                                'multiple' => true,
                                'btn_add' => false,
                                'query'    => $userGroupsQuery
                            ));
                        }
                    $formMapper->end();
                }

            $formMapper->end();
        ;

        if ($this->isAdmin()) {
            $formMapper
                ->with('Status', array('class' => 'col-md-6'))
                    ->add('enabled', null, array('required' => false))
                ->end()
            ;
        }

        /*if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $formMapper->with('Roles')
                ->add('realRoles', 'sonata_security_roles', array(
                    'label'    => 'form.label_roles',
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false
                ))
            ->end();
        }*/

        if ($this->id($this->getSubject())) {
            $this->getRequest()->getSession()->set(User::ID_HANDLER, $this->id($this->getSubject()));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('firstname')
            ->add('lastname')
            ->add('groups')
            //->add('gender')
            ->add('enabled')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);

        $listMapper
            ->addIdentifier('name', null, array('label' => 'User Name'))
            ->add('groups', null, array('template' => 'CanabelleCMSUserBundle:UserAdmin:list_groups_field.html.twig'))
            ->add('enabled', null, array('editable' => $this->isAdmin()))
        ;

        if ($this->isGranted('ROLE_SUPER_ADMIN')/*$this->isGranted('ROLE_ALLOWED_TO_SWITCH')*/) {
            $listMapper
                ->add('impersonating', 'string', array('template' => 'SonataUserBundle:Admin:Field/impersonating.html.twig'))
            ;
        }

        if ($this->isAdmin()) {
            $listMapper->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                )
            ));
        }
    }

    /**
     * @param null|User $object
     * @return bool
     */
    public function isAdmin($object = null)
    {
        return $this->isGranted(self::$ROLE_ADMIN) || ($object ? $this->isGranted('ADMIN', $object) : $this->isGranted('ADMIN'));
    }

    /**
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->isGranted('ROLE_SUPER_ADMIN');
    }

    /**
     * {@inheritdoc}
     * @var User $object
     */
    public function isGranted($name, $object = null)
    {
        switch($name) {
            case 'ROLE_SUPER_ADMIN':
                break;
            default:
                $isAdmin =
                    (!$object && parent::isGranted('ADMIN'))
                    ||
                    ($object && parent::isGranted('ADMIN', $object))
                    ||
                    parent::isGranted(self::$ROLE_ADMIN)
                    ||
                    parent::isGranted('ROLE_SUPER_ADMIN');

                if ($isAdmin) {
                    return true;
                }
        }

        switch($name) {
            case 'CREATE':
                if (!$this->isAdmin()) {
                    return false;
                }
                break;
            case 'EDIT':
                if ($object && $this->container->get('security.token_storage')->getToken()->getUser()->getId() == $object->getId()) {
                    return true;
                }
                break;
        }

        return parent::isGranted($name, $object);
    }

    /**
     * @return bool
     */
    public function showCreateButton()
    {
        return $this->isAdmin();
    }

    public function showAddBtnInDashboard()
    {
        return $this->isAdmin();
    }

    public function showListBtnInDashboard()
    {
        return $this->isAdmin();
    }

    public function showInAddBlock()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormBuilder()
    {
        $this->formOptions['data_class'] = $this->getClass();

        $options = $this->formOptions;
        $options['validation_groups'] = 'Default';

        $formBuilder = $this->getFormContractor()->getFormBuilder( $this->getUniqid(), $options);

        $this->defineFormBuilder($formBuilder);

        return $formBuilder;
    }

    /**
     * @return array
     */
    public function getFormTheme()
    {
        $formTheme = parent::getFormTheme();
        return array_merge($formTheme, array('CanabelleCMSUserBundle:UserAdmin:admin_fields.html.twig'));
    }

    public function createQuery($context = 'list')
    {
        /** @var QueryBuilder $query */
        $query = parent::createQuery($context);
        $query->addOrderBy($query->getRootAlias() . '.lastname', 'asc');
        $query->addOrderBy($query->getRootAlias() . '.firstname', 'asc');

        if ($context == 'list') {
            if (!$this->isAdmin()) {
                $user = $this->container->get('security.token_storage')->getToken()->getUser();
                $query->andWhere($query->getRootAlias() . '.id = :userId');
                $query->setParameter('userId', $user->getId());

                $object = $query->getQuery()->getSingleResult();
                $url = $this->generateUrl('edit', array('id' => $object->getId()));
                header('Location: ' . $url);
                exit;
            } elseif (!$this->isSuperAdmin()) {
                $query->andWhere($query->getRootAlias() . '.roles NOT LIKE :superAdminRole');
                $query->setParameter('superAdminRole', '%ROLE_SUPER_ADMIN%');
            }
        }

        return $query;
    }

    /**
     * @param User $object
     * @return mixed|void
     */
    public function prePersist($object)
    {
    }

    public function preUpdate($object)
    {
        parent::preUpdate($object);
    }

    /**
     * Generates the breadcrumbs array
     *
     * Note: the method will be called by the top admin instance (parent => child)
     *
     * @param string                       $action
     * @param \Knp\Menu\ItemInterface|null $menu
     *
     * @return array
     */
    public function buildBreadcrumbs($action, MenuItemInterface $menu = null)
    {
        if (isset($this->breadcrumbs[$action])) {
            return $this->breadcrumbs[$action];
        }

        if (!$menu) {
            $menu = $this->menuFactory->createItem('root');

            $menu = $menu->addChild(
                $this->trans($this->getLabelTranslatorStrategy()->getLabel('dashboard', 'breadcrumb', 'link'), array(), 'Ok99PrivateZoneAdminBundle'),
                array(
                    'uri' => $this->routeGenerator->generate('sonata_admin_dashboard'),
                    'attributes' => array(
                        'icon' => '<i class="fa fa-dashboard"></i>'
                    )
                )
            );
        }

        $menu = $menu->addChild(
            $this->trans($this->getLabel(), array(), $this->translationDomain),
            array('uri' => $this->hasRoute('list') && $this->isGranted('LIST') ? $this->generateUrl('list') : null)
        );

        $childAdmin = $this->getCurrentChildAdmin();

        if ($childAdmin) {
            $id = $this->request->get($this->getIdParameter());

            $menu = $menu->addChild(
                $this->toString($this->getSubject()),
                array('uri' => $this->hasRoute('edit') && $this->isGranted('EDIT') ? $this->generateUrl('edit', array('id' => $id)) : null)
            );

            return $childAdmin->buildBreadcrumbs($action, $menu);

        } elseif ($this->isChild()) {

            if ($action == 'list') {
                $menu->setUri(false);
            } elseif ($action != 'create' && $this->hasSubject()) {
                $menu = $menu->addChild($this->toString($this->getSubject()));
            } else {
                $menu = $menu->addChild(
                    $this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_%s', $this->getClassnameLabel(), $action), 'breadcrumb', 'link'))
                );
            }

        } elseif ($action != 'list' && $this->hasSubject()) {
            $menu = $menu->addChild($this->toString($this->getSubject()));
        } elseif ($action != 'list') {
            $menu = $menu->addChild(
                $this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_%s', $this->getClassnameLabel(), $action), 'breadcrumb', 'link'))
            );
        }

        return $this->breadcrumbs[$action] = $menu;
    }
}
