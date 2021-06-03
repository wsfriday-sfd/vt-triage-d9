<?php

namespace Drupal\triage\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class TriageAccessController extends ControllerBase
{
    public function checkTriageAccess($node)
    {
        $actualNode = Node::load($node);
        return AccessResult::allowedIf($actualNode->bundle() === 'triage_page');
    }
}