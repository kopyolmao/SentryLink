<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\MailerService;
use App\Libraries\PortalService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $portal = new PortalService(db_connect(), session(), new MailerService());
        $userId = $portal->currentUserId();
        $role   = $portal->currentRole();
        $roles  = is_array($arguments) ? $arguments : [];

        if (! $userId || ! $role) {
            $loginRole = $roles[0] ?? 'student';

            return redirect()->to(site_url($portal->roleLogin($loginRole)));
        }

        if ($roles !== [] && ! in_array($role, $roles, true)) {
            return redirect()->to(site_url($portal->roleHome($role)));
        }

        if (! $portal->currentUser()) {
            $loginRole = $roles[0] ?? 'student';

            return redirect()->to(site_url($portal->roleLogin($loginRole)));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
