<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\MailerService;
use App\Libraries\PortalService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $portal = new PortalService(db_connect(), session(), new MailerService());
        $role   = $portal->currentRole();

        if ($portal->currentUserId() && $role) {
            return redirect()
                ->to(site_url($portal->roleHome($role)))
                ->noCache()
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('Expires', '0');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response
            ->noCache()
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');
    }
}
