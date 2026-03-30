<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Security;

enum ContactsPermission: string
{
    case VIEW_CUSTOMERS   = 'crm.contacts.view_customers';
    case CREATE_CUSTOMER  = 'crm.contacts.create_customer';
    case UPDATE_CUSTOMER  = 'crm.contacts.update_customer';
}
