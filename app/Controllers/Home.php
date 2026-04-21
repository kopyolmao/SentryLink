<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $user = $this->portal->currentUser();
        $role = $this->portal->currentRole();

        if (is_array($user) && is_string($role) && $role !== '') {
            return redirect()->to(site_url($this->portal->roleHome($role)));
        }

        return redirect()->to(site_url('s/auth/login'));
    }

    public function systemLogo()
    {
        $logoPath = ROOTPATH . 'Logo.html';

        if (! is_file($logoPath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $content = file_get_contents($logoPath);
        if ($content === false) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setContentType('text/html', 'UTF-8')
            ->setBody($content);
    }
}
