<?php
function canView($module)
{
    return !empty($_SESSION['permissions'][$module]['view']);
}

function canAdd($module)
{
    return !empty($_SESSION['permissions'][$module]['add']);
}

function canEdit($module)
{
    return !empty($_SESSION['permissions'][$module]['edit']);
}

function canDelete($module)
{
    return !empty($_SESSION['permissions'][$module]['delete']);
}
