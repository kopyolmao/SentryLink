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
}
