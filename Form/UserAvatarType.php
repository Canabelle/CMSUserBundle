<?php

namespace Canabelle\CMSUserBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserAvatarType extends TextType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'user_avatar';
    }
}